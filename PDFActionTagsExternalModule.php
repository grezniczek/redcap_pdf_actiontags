<?php

namespace RUB\PDFActionTagsExternalModule;

use ExternalModules\AbstractExternalModule;

/**
 * ExternalModule class for PDF Action Tags.
 */
class PDFActionTagsExternalModule extends AbstractExternalModule {

    public $IDENTITY = "27af2aea-2563-4a5e-9c33-748c89d1cb15";

    function redcap_every_page_top($project_id = null) {

        // Do not run on non-project pages.
        $project_id = empty($project_id) ? 0 : is_numeric($project_id) ? intval($project_id) : 0;
        if ($project_id < 1) return;

        // Inject JS into project pages that redirects all PDF links to this module.
        $pdfUrl = str_replace(APP_PATH_WEBROOT_FULL, "/", $this->getUrl("pdf.php")) . "&";
        $search = APP_PATH_WEBROOT . "PDF/index.php?pid={$project_id}";
?>
        <script>
            // PDF Action Tags External Module
            $(function() {
                $('a[href*="PDF/index.php"]').each(function(index, el) {
                    const a = $(el)
                    a.attr('href', a.attr('href').replace('<?php echo $search ?>', '<?php echo $pdfUrl ?>'))
                })
                $('a[onclick*="PDF/index.php"]').each(function(index, el) {
                    const a = $(el)
                    a.attr('onclick', a.attr('onclick').replace("app_path_webroot+'PDF/index.php?pid='+pid+'&page", "'<?php echo $pdfUrl ?>render_page"))
                })
            })
        </script>
<?php
        // Insert the action tag descriptions (only on Design)
        if (strpos(PAGE_FULL, "/Design/online_designer.php") !== false) {
            $template = file_get_contents(dirname(__FILE__)."/actiontags_info.html");
            $replace = array(
                "{PREFIX}" => $this->PREFIX
            );
            print str_replace(array_keys($replace), array_values($replace), $template);
        }
    }


    /**
     * Extracts the parameter of an action tag
     *
     * @param $misc string String in which to search for an actiontag
     * @param $tag string Actiontag to look for
     * @return bool|string Parameter content or false if there was no parameter
     */
    private static function getActiontagParam($misc, $tag)
    {
        $param = false;
        $pos = strpos($misc, $tag."=");
        if ($pos !== false) {
            $rest = substr($misc, $pos + strlen($tag) + 1);
            $quotechar = substr($rest, 0, 1);
            $end = strpos($rest, $quotechar, 1);
            if ($end !== false) {
                $param_len = strpos($rest, $quotechar, 1) - 1;
                $param = substr($rest, 1, $param_len);
            }
        }
        return $param;
    }

    /**
     * Applies PDF action tags
     *
     * @param $metadata array The metadata array generated in redcap/PDF/index.php
     * @param $Data array The data array from redcap/PDF/index.php
     * @return array A filtered metadata arry. Use this to replace $metadata just before passing to renderPDF at the bottom of redcap/PDF/index.php
     */
    public static function applyActiontags($metadata, &$Data)
    {
        // List of element types that support the @PDF-NOENUM actiontag
        $noenum_supported_types = array ( 'sql', 'select', 'radio' );

        // 'form with saved data' or 'form (empty)' ?
        $with_data = !(isset($Data['']) || empty($Data));

        // Get an idea, where data values are present
        $dv = array();
        foreach ($metadata as $attr) {
            $field_data_value = '';
            if ($with_data) {
                // Check specific value
                foreach ($Data as $this_record => $event_data) {
                    foreach ($event_data as $this_event_id => $field_data) {
                        $field_data_value = $field_data[$attr['field_name']];
                    }
                }
            }
            // Store result; value or false when no value is present
            $dv[$attr['field_name']] = $field_data_value != '' ? $field_data_value : false;
        }

        $filtered_metadata = array();
        foreach ($metadata as $attr) {

            $fieldname = $attr['field_name'];

            // Only process other PDF action tags if @HIDDEN-PDF is _not_ present
            if (strpos($attr['misc'], "@HIDDEN-PDF") === false) {

                $include = true;

                // @PDF-HIDDENNODATA
                if ($include && strpos($attr['misc'], '@PDF-HIDDENNODATA') !== false) {
                    // Supplied with parameter?
                    if ($targetfield = self::getActiontagParam($attr['misc'], '@PDF-HIDDENNODATA')) {
                        if (array_key_exists($targetfield, $dv)) {
                            $include = $dv[$targetfield] !== false;
                        }
                    }
                    else {
                        $include = $with_data;
                    }
                }

                // @PDF-HIDDENDATA
                if ($include && strpos($attr['misc'], '@PDF-HIDDENDATA') !== false) {
                    // Supplied with parameter?
                    if ($targetfield = self::getActiontagParam($attr['misc'], '@PDF-HIDDENDATA')) {
                        if (array_key_exists($targetfield, $dv)) {
                            $include = !($dv[$targetfield] !== false);
                        }
                    }
                    else {
                        $include = !$with_data;
                    }
                }

                // @PDF-WHITESPACE (only applies when there is no data present)
                if ($include && $dv[$fieldname] === false && strpos($attr['misc'], '@PDF-WHITESPACE=') !== false) {
                    // get parameter value
                    $param = self::getActiontagParam($attr['misc'], '@PDF-WHITESPACE');
                    if (is_numeric($param)) {
                        $n = max(0, (int)$param); // no negativ values!
                        // insert placeholder data
                        $whitespace = str_repeat("\n", $n);
                        $attr['element_label'] = $attr['element_label'].$whitespace;
                    }
                }

                // @PDF-FIELDNOTEEMPTY
                if ($include && !$with_data && strpos($attr['misc'], '@PDF-FIELDNOTEEMPTY=') !== false) {
                    // get parameter value
                    $note = self::getActiontagParam($attr['misc'], '@PDF-FIELDNOTEEMPTY');
                    if ($note !== false) {
                        $attr['element_note'] = "$note";
                    }
                }

                // @PDF-FIELDNOTEDATA
                if ($include && $with_data && strpos($attr['misc'], '@PDF-FIELDNOTEDATA=') !== false) {
                    // get parameter value
                    $note = self::getActiontagParam($attr['misc'], '@PDF-FIELDNOTEDATA');
                    if ($note !== false && $dv[$fieldname] !== false) { // only apply when actual data is present
                        $attr['element_note'] = "$note";
                    }
                }

                // @PDF-NOENUM
                if ($include && strpos($attr['misc'], '@PDF-NOENUM') !== false && in_array($attr['element_type'], $noenum_supported_types)) {
                    // check if data is present and if so, replace key values with data from the enums
                    if ($dv[$fieldname] !== false) {
                        // make the enum 'accessible'
                        $enumvalues = array();
                        $lines = explode("\\n", $attr['element_enum']);
                        foreach ($lines as $l) {
                            $kv = explode(",", $l, 2);
                            $key = trim($kv[0]);
                            $value = trim($kv[1]);
                            $enumvalues[$key] = $value;
                        }
                        // replace data value with text from enum
                        foreach ($Data as $this_record => &$event_data) {
                            foreach ($event_data as $this_event_id => &$field_data) {
                                $keyvalue = $field_data[$attr['field_name']];
                                // if value is not blank
                                if ($keyvalue != '') {
                                    $field_data[$attr['field_name']] = $enumvalues[$keyvalue];
                                }
                            }
                        }
                    }
                    // Change element type to 'text' so a line is displayed in the PDF if there is no value
                    $attr['element_type'] = "text";
                }

                // @PDF-DATANOENUM
                if ($include && strpos($attr['misc'], '@PDF-DATANOENUM') !== false && in_array($attr['element_type'], $noenum_supported_types)) {
                    if ($dv[$fieldname] !== false) {
                        // make the enum 'accessible'
                        $enumvalues = array();
                        $lines = explode("\\n", $attr['element_enum']);
                        foreach ($lines as $l) {
                            $kv = explode(",", $l, 2);
                            $key = trim($kv[0]);
                            $value = trim($kv[1]);
                            $enumvalues[$key] = $value;
                        }
                        // replace data value with text from enum
                        foreach ($Data as $this_record => &$event_data) {
                            foreach ($event_data as $this_event_id => &$field_data) {
                                $keyvalue = $field_data[$attr['field_name']];
                                // if value is not blank
                                if ($keyvalue != '') {
                                    $field_data[$attr['field_name']] = $enumvalues[$keyvalue];
                                }
                            }
                        }
                        // Change element type to 'text' so a line is displayed in the PDF if there is no value
                        $attr['element_type'] = "text";
                    }
                }

                // Conditionally add to the filtered metadata list
                if ($include) {
                    $filtered_metadata[] = $attr;
                }
            }
        }
        return $filtered_metadata;
    }


}