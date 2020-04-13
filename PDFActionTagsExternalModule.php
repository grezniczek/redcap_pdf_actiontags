<?php

namespace RUB\PDFActionTagsExternalModule;

use ExternalModules\AbstractExternalModule;

/**
 * ExternalModule class for PDF Action Tags.
 */
class PDFActionTagsExternalModule extends AbstractExternalModule {

    function redcap_every_page_top($project_id = null) {

        // Do not run on non-project pages.
        $project_id = empty($project_id) ? 0 : is_numeric($project_id) ? intval($project_id) : 0;
        if ($project_id < 1) return;

        // Insert the action tag descriptions (only on Design)
        if (strpos(PAGE_FULL, "/Design/online_designer.php") !== false) {
            $template = file_get_contents(dirname(__FILE__)."/actiontags_info.html");
            $replace = array(
                "{PREFIX}" => $this->PREFIX,
                "{HELPTITLE}" => $this->tt("helptitle"),
                "{ADD}" => $this->tt("button_add"),
                "{PDF-HIDDEN}" => $this->tt("pdf_hidden_desc"),
                "{PDF-NOENUM}" => $this->tt("pdf_noenum_desc"),
                "{PDF-FIELDNOTE-BLANK}" => $this->tt("pdf_fieldnote_blank_desc"),
                "{PDF-FIELDNOTE-DATA}" => $this->tt("pdf_fieldnote_data_desc"),
                "{PDF-WHITESPACE}" => $this->tt("pdf_whitespace_desc")
            );
            print str_replace(array_keys($replace), array_values($replace), $template);
        }
    }

    /**
     * Allows for the interception of a PDF being generated or the manipulation of the $metadata or $data arrays that will be used to generate the PDF.
     * @param int $project_id The project ID number of the REDCap project.
     * @param array $metadata The metadata array that will be passed to the renderPDF() function for building the content structure of the PDF.
     * @param array $data The data array that will be passed to the renderPDF() function for injecting stored data values into the content structure of the PDF to display the data from one or more records in the project.
     * @param string $instrument The unique form name of the instrument being exported as a PDF. Note: If instrument=NULL, this implies that ALL instruments in the project will be included in the PDF.
     * @param string $record The name of the single record whose data will be included in the PDF. Note: If record=NULL, this implies a blank PDF is being exported (i.e., with no record data).
     * @param int $event_id The current event_id for the record whose data will be included in the PDF.
     * @param int $instance The repeating instance number of the current repeating instrument/event for the record whose data will be included in the PDF.
     */
    function redcap_pdf($project_id, $metadata, $data, $instrument, $record, $event_id, $instance = 1) {

        self::applyActiontags($metadata, $data);

        return array('metadata'=>$metadata, 'data'=>$data);
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
    private static function applyActiontags(&$metadata, &$Data)
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
                $include = false;
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
                    $attr['element_label'] = $attr['element_label'].$whitespace . "&nbsp;";
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
                        // Extract the enum values to be available for data replacement.
                        $enumvalues = array();
                        $lines = explode("\\n", $attr['element_enum']);
                        foreach ($lines as $l) {
                            $kv = explode(",", $l, 2);
                            $key = trim($kv[0]);
                            $value = trim($kv[1]);
                            $enumvalues[$key] = $value;
                        }
                        // Replace enum code values with corresponding text values.
                        foreach ($Data as $this_record => &$event_data) {
                            // Repeat instance (instrument or entire event)?
                            if (array_key_exists("repeat_instances", $event_data)) {
                                foreach ($event_data["repeat_instances"] as $event_id => &$repeat_data) {
                                    foreach ($repeat_data as $instrument => &$instrument_data) {
                                        foreach ($instrument_data as $this_instance => &$field_data) {
                                            $keyvalue = $field_data[$attr['field_name']];
                                            if ($keyvalue != '') {
                                                $field_data[$attr['field_name']] = $enumvalues[$keyvalue];
                                            }
                                        }
                                    }
                                }
                            }
                            // Regular, non-repeating data.
                            foreach ($event_data as $this_event_id => &$field_data) {
                                $keyvalue = $field_data[$attr['field_name']];
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
        $metadata = $filtered_metadata;
    }
}