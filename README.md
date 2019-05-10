# REDCap PDF @ActionTags

A REDCap External Module that provides action tags for controlling PDF output.

Please see the [CHANGLOG](CHANGELOG.md) to learn about changes and bugfixes. Version 1.1.0 introduced some **breaking** changes.

## Requirements

- REDCAP 8.1.0 or newer (tested with REDCap 8.11.11 and 9.0.0).

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

Note, some action tags will only affect rendering of _blank_ PDFs and will not work when data is present!

- `@PDF-HIDDEN="all|blank|data"` or `@HIDDEN-PDF="all|blank|data"`

  When present, the field will not be shown on the PDF. By default, the parameter `all` is assumed (or when a parameter other than _blank_ or _data_ is given). When `blank` is specified, the field will be hidden on blank PDFs only. In case of `data`, the field will only be hidden on PDFs with data.

- `@PDF-NOENUM="all|blank|data"`

  When present in a field (sql, select, radio), the enumeration of the list members will be suppressed. Instead, the field is shown as if it was a text field, i.e. it will be represented with an empty line.  When a data value is present, the value will be preserved.

  A parameter can be supplied: @PDF-NOENUM="_all|blank|data_". This will limit the scope of this action tag to blank PDFs or PDFs with data only. All values other than _blank_ and _data_ are treated equivalent to _all_.

- `@PDF-FIELDNOTE-BLANK="text"`
  
  When present, the field note will be replaced by _'text'_ **on blank PDFs only.**

- `@PDF-FIELDNOTE-DATA="text"`

  When present, the field note will be replaced by _'text'_ **on PDFs with saved data only.**

- `@PDF-WHITESPACE="number of lines"`

  When present, the given number of empty lines will be added to a field's label, pushing down the next field on the page. The number of lines must be a positive integer. **This action tag only affects blank PDFs!**

## Acknowledgements

- This is the External Module-version of an [earlier project](https://github.com/grezniczek/redcap-pdf-actiontags) that required modification of the REDCap source code.

- Thanks to Luke Stevens for suggesting how to implement this despite the lack of a _hook at the right place_.