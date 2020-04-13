# PDF @ActionTags - Manual Testing Procedure

Version 1 - 2020-04-13

## Prerequisites

- A test project with the _PDF @ActionTags_ module enabled.  
  Use [this project xml file](?prefix=redcap_pdf_actiontags&page=Demo/TestProject.xml) (XML metadata and data) to generate the test project.
- There are no system or project configuration options for this module.

## Test Procedure

1. Go to the test project and open the _Record Home Page_ for the first record.
1. In the _Choose action for record_ menu, select _Download PDF of record data for all instruments/events_.
1. View the resulting PDF and verify the following:
   - It should have a total of 10 pages (1x First, 6x Second, 3x Third Instrument).
   - The result fits the descriptions given in the fields labels.
1. Open the data entry form for the first repeat instance of the Second Instrument.
1. From the _Download PDF of instrument(s)_ menu, choose "This data entry form with saved data".
1. View the resulting PDF and verify the following:
   - The PDF (1 page) contains the correct instrument.
   - The result fits the descriptions given in the field labels.
1. Go to the second instance of the Second Instrument.
1. From the _Download PDF of instrument(s)_ menu, choose "This data entry (blank)".
1. View the resulting PDF and verify the following:
   - The PDF (1 page) contains the correct instrument (Second Instrument).
   - The result fits the descriptions given in the field labels.
1. From the _Download PDF of instrument(s)_ menu, choose "All forms/surveys (blank)".
1. View the resulting PDF and verify the following:
   - The PDF has 4 pages (First Instrument on pages 1 and 2, Second Instrument on page 3, and Third Instrument on page 4).
   - The result fits the descriptions given in the field labels.
1. Go to the _Data Exports, Reports, and Stats_ page, and activate the _Other Export Options_ tab.
1. Click on _Download PDF with all data_.
1. View the resulting PDF and verify the following:
   - The PDF has 15 pages (record 1: pages 1-10, record 2: pages 11-14, record 3: page 15).
   - The result fits the descriptions given in the field labels.
1. Go to the _Survey Distribution Tools_ page and click the _Open public survey_ button.
1. Fill in the survey and submit it, then click the _Download_ button to download the survey response PDF.
1. View the resulting PDF and verify the following:
   - The PDF has 1 page.
   - The result fits the descriptions given in the field labels.
1. Go tho the _Record Home Page_ for the record containing the just submitted survey (should be 4).
1. In the _Choose action for record_ menu, select and confirm "Delete record (all forms/events).

Done.

## Reporting Errors

Before reporting errors:
- Make sure there is no interference with any other external module by turning off all others and repeating the tests.
- Check if you are using the latest version of the module. If not, see if updating fixes the issue.

To report an issue:
- Please report errors by opening an issue on [GitHub](https://github.com/grezniczek/redcap_pdf_actiontags/issues) or on the community site (please tag @gunther.rezniczek). 
- Include essential details about your REDCap installation such as **version** and platform (operating system, PHP version).
- If the problem occurs only in conjunction with another external module, please provide its details (you may also want to report the issue to that module's authors).
