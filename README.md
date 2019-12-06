# REDCap PDF @ActionTags

A REDCap External Module that provides action tags for controlling PDF output.

Please see the [CHANGELOG](?prefix=redcap_pdf_actiontags&page=CHANGELOG.md) to learn about changes and bugfixes. Version 2.0.0 takes advantage of the new `redcap_pdf` hook, which removed all previous limitations.

**Note**, however, that through the introduction of the _native_ `@HIDDEN-PDF` action tag, the one provided by this module will not work when used with the `blank` or `data` modifier (as the REDCap-provided action tag will force this to always be `all`). Thus, if you have been using this tag with one of those modifiers, replace it with the alternative `@PDF-HIDDEN`.

To test the features of this module, use this [test project](?prefix=redcap_pdf_actiontags&page=Demo/TestProject.xml) (XML metadata and data).

## Requirements

- REDCAP 9.5.0 or newer.

## How It Works

This module intercepts calls to the PDF generating function of REDCap and modifies the data dictionary and/or the data to fit what has been set via the action tags provided by this module.

## Installation

- Clone this repo into `<redcap-root>/modules/redcap_pdf_actiontags_v<version-number>`, or
- Obtain this module from the Consortium [REDCap Repo](https://redcap.vanderbilt.edu/consortium/modules/index.php) via the Control Center.
- Go to _Control Center > Technical / Developer Tools > External Modules_ and enable REDCap JavaScript Injector.

## Configuration

There are no configuration options besides those provided by the External Module Framework.

## Action Tags

Note, some action tags will only affect rendering of _blank_ PDFs and will not work when data is present! Furthermore, _compact_ PDFs will override/supersede some of these settings (e.g., enumerations will be reduced always, and fields with blank values are not shown).

- `@PDF-HIDDEN="all|blank|data"`

  When present, the field will not be shown on the PDF. By default, the parameter `all` is assumed (or when a parameter other than _blank_ or _data_ is given; this is synonymous to REDCap's built-in `@HIDDEN-PDF`). When `blank` is specified, the field will be hidden on blank PDFs only. In case of `data`, the field will only be hidden on PDFs with data.

- `@PDF-NOENUM="all|blank|data"`

  When present in a field (sql, select, radio), the enumeration of the list members will be suppressed. Instead, the field is shown as if it was a text field, i.e. it will be represented with an empty line.  When a data value is present, the value will be preserved.

  A parameter can be supplied: @PDF-NOENUM="_all|blank|data_". This will limit the scope of this action tag to blank PDFs or PDFs with data only. All values other than _blank_ and _data_ are treated equivalent to _all_.

- `@PDF-FIELDNOTE-BLANK="text"`
  
  When present, the field note will be replaced by _'text'_ **on blank PDFs only.**

- `@PDF-FIELDNOTE-DATA="text"`

  When present, the field note will be replaced by _'text'_ **on PDFs with saved data only.**

- `@PDF-WHITESPACE="number of lines"`

  When present, the given number of empty lines will be added to a field's label, pushing down the next field on the page. The number of lines must be a positive integer. **This action tag only affects blank PDFs!**
