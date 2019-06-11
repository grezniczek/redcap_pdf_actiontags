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

        // Inject JS into project pages that redirects all PDF links to this module ...
        $pdfUrlPrefix = dirname(APP_PATH_WEBROOT);
        $pdfUrlPrefix = strlen($pdfUrlPrefix) > 1 ? $pdfUrlPrefix : "";
        $pdfUrl = $pdfUrlPrefix . str_replace(APP_PATH_WEBROOT_FULL, "/", $this->getUrl("pdf.php")) . "&";
        $search = APP_PATH_WEBROOT . "PDF/index.php?pid={$project_id}";

        // ... depending on the type of page we are on.
        if (strpos(PAGE_FULL, "/Design/online_designer.php") !== false) {
            $search .= "&page=";
            $pdfUrl .= "render_page=";
?>
        <script>
            // PDF Action Tags External Module (Designer)
            $(function() {
                $('a[href*="PDF/index.php"]').each(function(index, el) {
                    const a = $(el)
                    a.attr('href', a.attr('href').replace('<?=$search?>', '<?=$pdfUrl?>'))
                })
            })
        </script>
<?php
        }
        else {
?>
        <script>
            // PDF Action Tags External Module (Data Entry)
            $(function() {
                $('a[href*="PDF/index.php"]').each(function(index, el) {
                    const a = $(el)
                    a.attr('href', a.attr('href').replace('<?=$search?>', '<?=$pdfUrl?>'))
                })
                $('a[onclick*="PDF/index.php"]').each(function(index, el) {
                    const a = $(el)
                    a.attr('onclick', a.attr('onclick').replace("app_path_webroot+'PDF/index.php?pid='+pid+'&page", "'<?=$pdfUrl?>render_page"))
                    a.attr('onclick', a.attr('onclick').replace("app_path_webroot+'PDF/index.php?pid='+pid+'&", "'<?=$pdfUrl?>"))
                })
            })
        </script>
<?php
        }
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

        // Blank? ('form with saved data' or 'form (empty)')
        $blank = (isset($Data['']) || empty($Data));

        $filtered_metadata = array();
        foreach ($metadata as $attr) {

            $include = true;

            // @HIDDEN-PDF/@PDF-HIDDEN
            if ($include && strpos($attr['misc'], "@HIDDEN-PDF") !== false) {
                // Get parameter value.
                $param = strtolower(self::getActiontagParam($attr['misc'], '@HIDDEN-PDF'));
                if ($param == "blank") {
                    $include = !$blank;
                }
                else if ($param == "data") {
                    $include = $blank;
                }
                else {
                    $include = false;
                }
            } 
            
            if ($include && strpos($attr['misc'], "@PDF-HIDDEN") !== false) {
                // Get parameter value.
                $param = strtolower(self::getActiontagParam($attr['misc'], '@PDF-HIDDEN'));
                if ($param == "blank") {
                    $include = !$blank;
                }
                else if ($param == "data") {
                    $include = $blank;
                }
                else {
                    $include = false;
                }
            }

            // @PDF-WHITESPACE (only applies to blank PDFs)
            if ($include && $blank && strpos($attr['misc'], '@PDF-WHITESPACE=') !== false) {
                // Get parameter value.
                $param = self::getActiontagParam($attr['misc'], '@PDF-WHITESPACE');
                if (is_numeric($param)) {
                    $n = max(0, (int)$param); // no negativ values!
                    // insert placeholder data
                    $whitespace = str_repeat("\n", $n);
                    $attr['element_label'] = $attr['element_label'].$whitespace;
                }
            }

            // @PDF-NOENUM
            if ($include && strpos($attr['misc'], '@PDF-NOENUM') !== false && in_array($attr['element_type'], $noenum_supported_types)) {
                // Get parameter value.
                $param = strtolower(self::getActiontagParam($attr['misc'], '@PDF-NOENUM'));
                // Should the action tag be applied?
                $apply = true;
                if ($param == "blank" && !$blank) $apply = false;
                if ($param == "data" && $blank) $apply = false;
                if ($apply) {
                // Check if data is present and if so, replace key values with data from the enums.
                    if (!$blank) {
                        // Make the enum 'accessible'.
                        $enumvalues = array();
                        $lines = explode("\\n", $attr['element_enum']);
                        foreach ($lines as $l) {
                            $kv = explode(",", $l, 2);
                            $key = trim($kv[0]);
                            $value = trim($kv[1]);
                            $enumvalues[$key] = $value;
                        }
                        // Replace data value with text from enum.
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
                    // Change element type to 'text' so a line is displayed in the PDF if there is no value.
                    $attr['element_type'] = "text";
                }
            }

            // @PDF-FIELDNOTE-BLANK (only applies to blank PDFs)
            if ($include && $blank && strpos($attr['misc'], '@PDF-FIELDNOTE-BLANK=') !== false) {
                // Get parameter value.
                $note = self::getActiontagParam($attr['misc'], '@PDF-FIELDNOTE-BLANK');
                if ($note !== false) {
                    $attr['element_note'] = "$note";
                }
            }

            // @PDF-FIELDNOTE-DATA (only applies to PDFs with saved data)
            if ($include && !$blank && strpos($attr['misc'], '@PDF-FIELDNOTE-DATA=') !== false) {
                // Get parameter value.
                $note = self::getActiontagParam($attr['misc'], '@PDF-FIELDNOTE-DATA');
                if ($note !== false) {
                    $attr['element_note'] = "$note";
                }
            }

            // Conditionally add to the filtered metadata list
            if ($include) {
                $filtered_metadata[] = $attr;
            }
        }
        return $filtered_metadata;
    }

    /**
     * Generates a GUID in the format xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx.
     */
    public static function GUID() 
    {
        if (function_exists('com_create_guid') === true) {
            return strtolower(trim(com_create_guid(), '{}'));
        }
        return strtolower(sprintf('%04X%04X-%04X-%04X-%04X-%04X%04X%04X', mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(16384, 20479), mt_rand(32768, 49151), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535)));
    }


}