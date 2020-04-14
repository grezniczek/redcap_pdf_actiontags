# PDF @ActionTags

A REDCap External Module that provides action tags for controlling PDF output.

**Note** that through the introduction of the _native_ `@HIDDEN-PDF` action tag, the one provided by this module will not work when used with the `blank` or `data` modifier (as the REDCap-provided action tag will force this to always be `all`). Thus, if you have been using this tag with one of those modifiers, replace it with the alternative `@PDF-HIDDEN`.

<span style="color:red">**WARNING:**</span> The current state of the `redcap_pdf` hook (up to at least REDCap 9.8.2) does not allow multiple modules implementing this hook to be active concurrently in a project. When using two or more such modules in a project, the _last to execute_ will _win_ and only its effects will be reflected in the generated PDFs!  
Note that this includes the classic hook function implementation as well. If there is a classic hook implementation of `redcap_pdf` that is returning data and/or metadata, it will override and thereby "disable" any module implementations.  
This is the case in the standard `hook_functions.php` file. So in order for any module implementing `redcap_pdf` to work at all, you **have to comment out the return statement in the hook file**!

## Requirements

- REDCAP 9.5.0 or newer.

## How It Works

This module implements the `redcap_pdf` hook, i.e. it modifies the data dictionary and/or the data to fit what has been set via the action tags provided by this module. REDCap then generates the PDF based on these modified information.

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

## Testing

Instructions for testing the module can be found [here](?prefix=redcap_pdf_actiontags&page=tests/PDFActionTagsManualTest.md).
To test the features of this module, use this [test project](?prefix=redcap_pdf_actiontags&page=Demo/TestProject.xml) (XML metadata and data).

## Changelog

Version | Description
------- | ----------------
v2.1.0  | Major bugfix: @PDF-NOENUM now works properly with repeating instruments/events.<br>Update README (integrate changelog).<br>Add instructions for testing the module.
v2.0.0  | Use the new `redcap_pdf` hook that became available with REDCap 9.5.0. This means, the module now works in _any_ context.
v1.1.3  | Bugfix: Add check to see if 'apache_setenv()' is available before calling it.
v1.1.2  | Bugfix: Fix broken PDF links within the Online Designer.
v1.1.1  | Bugfixes: Fix 404 error encountered when REDCap is installed in a subfolder of the webroot. Prevent issue with gzip compression (starting with REDCap >9.0.1, it seems the output of pages gets gzip'ed, including the PDFs generated when this module is active).
v1.1.0  | **Breaking Changes:** The action tags `@PDF-HIDDENDATA` and `@PDF-HIDDENNODATA` are removed in this version since they cannot work on PDFs for all records. The action tags `@PDF-FIELDNOTEEMPTY` and `@PDF-FIELDNOTEDATA` are renamed to `@PDF-FIELDNOTE-BLANK` and `@PDF-FIELDNOTE-DATA`, respectively (to ave consistent naming and to improve readability).<br>Feature Updates: The behavior of some action tags is slightly altered. Add `@PDF-HIDDEN` as a synonym for `@HIDDEN-PDF`. Allow control of the scope of some action tags by adding a parameter that limits them to affect all types of PDFs (_all_), blank PDFs only (_blank_), or PDFs with saved data only (_data_).<br>Bugfixes: Fix issue that not all PDF-generating links were replaced on Data Entry pages. Fix issue that action tag info would get added multiple times to the 'What are Action Tags?' popup in REDCap instances with active Messenger.
v1.0.0  | Initial release.
