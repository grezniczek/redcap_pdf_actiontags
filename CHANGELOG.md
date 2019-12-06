# @PDF ActionTags - REDCap EM

## CHANGELOG

### Version 2.0.0

- Uses the new `redcap_pdf` hook that became available with REDCap 9.5.0. This means, the module works now in _any_ context.

### Version 1.1.3

- Bugfixes
  - Added check to see if 'apache_setenv()' is available before calling it.

### Version 1.1.2

- Bugfixes
  - Fixed broken PDF links within the Online Designer.

### Version 1.1.1

- Bugfixes
  - Fixed 404 error encountered when REDCap is installed in a subfolder of the webroot.
  - Starting with REDCap >9.0.1, it seems the output of pages gets gzip'ed, including the PDFs generated when this module is active. This is now prevented by turning compression off in the PHP script.

### Version 1.1.0

- Breaking Changes
  - The action tags `@PDF-HIDDENDATA` and `@PDF-HIDDENNODATA` were removed, since they cannot work on PDFs for all records.
  - The action tags `@PDF-FIELDNOTEEMPTY` and `@PDF-FIELDNOTEDATA` have been renamed with `@PDF-FIELDNOTE-BLANK` and `@PDF-FIELDNOTE-DATA` to have consistent naming and to improve readability.

- Feature Updates
  - The behavior of some action tags has been slightly altered. See [documentation](README.md).
  - `@PDF-HIDDEN` has been added as a synonym for `@HIDDEN-PDF`.
  - The scope of some action tags can now be controlled by adding a parameter that limits them to affect all types of PDFs (_all_), blank PDFs only (_blank_), or PDFs with saved data only (_data_).

- Bugfixes
  - Fixed issue that not all PDF-generating links were replaced on Data Entry pages.
  - Fixed issue that action tag info would get added multiple times to the 'What are Action Tags?' popup in REDCap instances with active Messenger.

### Version 1.0.0

- Initial release.