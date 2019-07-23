<?php

namespace RUB\PDFActionTagsExternalModule;

// Check if this was properly called through the EM framework and within the context of a project.
$ok = isset($module) && isset($module->IDENTITY) && $module->IDENTITY == "27af2aea-2563-4a5e-9c33-748c89d1cb15" && isset($GLOBALS["project_id"]);

if (!$ok) {
    // Not allowed!
    http_response_code(403);
    die();
}

// Disable gzip-ing of output.
@ini_set('zlib.output_compression', 'Off');
@ini_set('output_buffering', 'Off');
@ini_set('output_handler', '');
if (function_exists('apache_setenv')) @apache_setenv('no-gzip', 1);

// Get the original PDF/index.php and split into pre-render and render.
$prerender = "";
$render = "";
$target = "prerender";
$handle = fopen(APP_PATH_DOCROOT."PDF".DS."index.php", "r");
if ($handle) {
    while (($line = fgets($handle)) !== false) {
        // Skip <?php and empty lines.
        if (trim($line) == "<?php") continue;
        // Switch target?
        if (strpos($line, "renderPDF(") !== false) $target = "render";

        if (strpos($line, "dirname(dirname(__FILE__))")) {
            // Fix require path.
            $line = str_replace("dirname(dirname(__FILE__))", "'".rtrim(APP_PATH_DOCROOT, DS)."'", $line);
        }
        // Add line.
        $$target .= $line;
    }
    fclose($handle);
} 
else {
    http_response_code(500);
    die("Failed read PDF logic. Please contact the REDCap administrator.");
}
if (!strlen(trim($render))) {
    http_response_code(500);
    die("Failed to extract render logic. Please contact the REDCap administrator.");
}


// Write PHP to temporary files.
$guid = PDFActionTagsExternalModule::GUID();
$prerenderFile = APP_PATH_TEMP . "{$module->PREFIX}_prerender_{$guid}.php";
$renderFile = APP_PATH_TEMP . "{$module->PREFIX}_render_{$guid}.php";
file_put_contents($prerenderFile, "<?php\n".$prerender);
file_put_contents($renderFile, "<?php\n".$render);

// Fix $_GET["page"].
unset($_GET["page"]);
if (isset($_GET["render_page"])) $_GET["page"] = $_GET["render_page"];

// Preprocess.
require $prerenderFile;

// Apply action tags.
$metadata = PDFActionTagsExternalModule::applyActiontags($metadata, $Data);

// Construct filename.
$filename = "";
if (isset($_GET['page'])) {
    $filename .= str_replace(" ", "", ucwords(preg_replace("/[^a-zA-Z0-9]/", " ", $ProjForms[$_GET["page"]]["menu"]))) . "_";
}
$filename .= str_replace(" ", "", ucwords(preg_replace("/[^a-zA-Z0-9]/", " ", $project_name)));
if (strlen($filename) > 30) {
    $filename = substr($filename, 0, 30);
}
if (isset($_GET['id']) || isset($_GET['allrecords'])) {
    $filename .= date("_Y-m-d_Hi");
}
$filename .= ".pdf";

// Render PDF.
require $renderFile;

// Clear and set new headers (this is necessary because renderPDF as called here outputs a string).
header_remove(); 
header("Content-type:application/pdf");
header("Content-Disposition:attachment; filename={$filename}");

// Remove temporary files.
@unlink($prerenderFile);
@unlink($renderFile);