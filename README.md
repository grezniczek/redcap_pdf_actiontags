# REDCap PDF @ActionTags

A REDCap External Module that provides action tags for controlling PDF output.

## Requirements

- REDCAP 8.1.0 or newer (tested with REDCap 8.11.11 on a system running PHP 7.0.33).

## How It Works

This module redirects links to PDF/index.php to itself. It replicates the functionality of PDF/index.php, but adds some additional logic to process any PDF action tags, thereby modifying the data and metadata used to generate the PDF.

## Limitations

As this module can only replace the calls to PDF/index.php in links displayed on various pages, users could still craft links to PDF/index.php and thus get access to PDFs which are not processed by this module. Thus, using this module **is not a safe way to protect sensible data**.

## Installation

- Clone this repo into `<redcap-root>/modules/redcap_pdf_actiontags_v<version-number>`, or
- Obtain this module from the Consortium [REDCap Repo](https://redcap.vanderbilt.edu/consortium/modules/index.php) via the Control Center.
- Go to _Control Center > Technical / Developer Tools > External Modules_ and enable REDCap JavaScript Injector.

## Configuration

There are no configuration options besides those provided by the External Module Framework.

## Action Tags

- `@HIDDEN-PDF`

   When present, the field will not be shown on the PDF.

- `@PDF-HIDDENDATA[="field_name"]`

   Omits a field from the PDF for 'form with saved data' only. If the name of another field is supplied as parameter, the tagged field will be omitted from 'form with saved data' when actual data is present in the specified field.

- `@PDF-HIDDENNODATA[="field_name"]`

   Omits a field from the PDF for 'form (empty)' or for 'form with saved data' when no actual data is present in the field specified in the parameter.

- `@PDF-NOENUM`

   When present in a field (sql, select, radio), the enumeration of the list members will be suppressed. Instead, the field is shown as if it was a text field, i.e. it will be represented with an empty line. When a data value is present, the value will be preserved.

- `@PDF-DATANOENUM`

   Behaves like @PDF-NOENUM, but only applies to 'form with saved data'.

- `@PDF-WHITESPACE="number of lines"`

   This action tag only applies to 'form (empty)' PDFs. If present, it will add the given number of empty lines to a field's label, pushing down the next field on the page. 'number of lines' must be a positive integer.

- `@PDF-FIELDNOTEEMPTY="text"`

   This action tag only applies to 'form (empty)' PDFs. When set, it replaces the field note of a field with the text supplied as parameter.

- `@PDF-FIELDNOTEDATA="text"`

   This action tag only applies to 'form with saved data' PDFs. When set, it replaces the field note of a field with the text supplied as the parameter, but only when there is an actual data value present in the field.

## Acknowledgements

- This is the External Module-version of an [earlier project](https://github.com/grezniczek/redcap-pdf-actiontags) that required modification of the REDCap source code.

- Thanks to Luke Stevens for suggesting how to implement this despite the lack of a _hook at the right place_.