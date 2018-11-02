# Changelog

## 1.0.0-beta.18 - 2018-11-02

> {warning} This release of Sprout Import includes a breaking change that will require you to update any @model namespaces in your import scripts. This update to the application structure was made to add more flexible import, seeding, and more to all plugins within the Sprout Plugin Suite. See the docs for a comprehensive [list of all namespace changes](https://sprout.barrelstrengthdesign.com/docs/import/installing-and-updating-craft-3.html#upgrading-to-v1-0-0-beta-17).

## 1.0.0-beta.17 - 2018-10-29

### Changed
- Updated Sprout Base requirement to v4.0.0

## 1.0.0-beta.16 - 2018-10-27

### Changed
- Updated Sprout Base requirement to v3.0.10

### Fixed
- Fixed bug in Order imports where orderLanguage was not found [#63]
- Fixed logic bug that occurred when using PHP 7.2 [#66]
- Fixed bug where console application could not access User

[#63]: https://github.com/barrelstrength/craft-sprout-import/issues/63
[#66]: https://github.com/barrelstrength/craft-sprout-import/issues/66

## 1.0.0-beta.15 - 2018-09-11

### Added
- Added support for assigning multiple User Groups when importing Users ([#65])
- Added support for relating multiple settings on Settings Importers

### Changed
- Updated Sprout Base requirement to v3.0.4

### Fixed
- Fixed bug where expiryDate attribute caused an error when importing an Entry ([#58])
- Fixed bug where variant field was required when importing to Commerce Products relations field ([#61])
- Fixed error when converting Sprout SEO Redirects to JSON using CSV helper ([#59])

[#58]: https://github.com/barrelstrength/craft-sprout-import/issues/58
[#59]: https://github.com/barrelstrength/craft-sprout-import/issues/59
[#61]: https://github.com/barrelstrength/craft-sprout-import/issues/61
[#65]: https://github.com/barrelstrength/craft-sprout-import/issues/65

## 1.0.0-beta.14 - 2018-08-01

## Changed
- Updated Sprout Base requirement to v3.0.0

## 1.0.0-beta.13 - 2018-06-25

### Added
- Added support for matching related elements in Element attributes such as entry.authorId, category.newParentId, and user.photoId ([#16], [#31])
- Added support for matching related settings in Element attributes such as entry.typeId and entry.sectionId ([#25])
- Added support for assigning a user to a User Group when importing User Elements ([#17])
- Added Seed Default support for Number Field 
- Added minRows and maxRows seed setting when seeding a Table field
- Added SettingsImporter::getRecordName()

## Changed
- Added support for matching elements based on all Element query parameters using `params` syntax in place of `matchBy`, `matchValue`, and `matchCriteria` [#39]
- The `updateElement` key now supports attributes and custom fields ([#38], [#39])
- Fixed bug where all items imported would be marked as Seed items ([#44])

### Fixed
- Fixed bug where `updateElement` key was creating a new element ([#38])
- Fixed bug where all items imported would be marked as Seed items ([#44])
- Fixed bug where EntryType Importer was not referencing Entry Element ([#32])
- Fixed various inconsistencies in how field limits were being set ([#43])
- Fixed issue when importing Orders from a console request

[#16]: https://github.com/barrelstrength/craft-sprout-import/issues/16
[#17]: https://github.com/barrelstrength/craft-sprout-import/issues/17
[#31]: https://github.com/barrelstrength/craft-sprout-import/issues/31
[#32]: https://github.com/barrelstrength/craft-sprout-import/issues/32
[#38]: https://github.com/barrelstrength/craft-sprout-import/issues/38
[#39]: https://github.com/barrelstrength/craft-sprout-import/issues/39
[#43]: https://github.com/barrelstrength/craft-sprout-import/issues/43
[#44]: https://github.com/barrelstrength/craft-sprout-import/issues/44

## 1.0.0-beta.12 - 2018-05-22

### Fixed
- Fixed bug when generating mock data for Asset field [#37] 
- Fixed bug where seeding did not respect character limit of Plain Text field [#36]
- Fixed bug where Weed tab did not render when using PostgreSQL [#21]

[#37]: https://github.com/barrelstrength/craft-sprout-import/issues/37
[#36]: https://github.com/barrelstrength/craft-sprout-import/issues/36
[#21]: https://github.com/barrelstrength/craft-sprout-import/issues/21

## 1.0.0-beta.11 - 2018-05-17

### Changed
- Updated Sprout Base requirement to ^2.0.2

### Fixed
- Fixed request dependency in Element Importer logic ([#35](https://github.com/barrelstrength/craft-sprout-import/issues/35))
- Fixed bug where controller behavior was not playing nicely with console requests ([#35](https://github.com/barrelstrength/craft-sprout-import/issues/35))
- Fixed issue where Assets Seed relatedMin was set to relatedMax

## 1.0.0-beta.10 - 2018-05-17

### Fixed
- Fixed release notes warning syntax

## 1.0.0-beta.9 - 2018-05-15

> {warning} If you have more than one Sprout Plugin installed, to avoid errors use the 'Update All' option.

### Added
- Added support for importing Craft Commerce Products and Product Variants
- Added support for importing Craft Commerce Orders
- Added support for importing Entry Revisions
- Added `enabledVersioning` override setting on Entry Element Importer
- Added example Craft Commerce Products JSON import file
- Added example Craft Commerce Orders JSON import file
- Added example Table Field JSON to import files
- Added example Entry Revisions JSON import file
- Added ElementImporter->afterSaveElement method
- Added minVersionRequired as Sprout Import v0.6.3

### Changed
- Updated BaseElementImporter => ElementImporter
- Updated BaseFieldImporter => FieldImporter
- Updated BaseSettingsImporter => SettingsImporter
- Updated BaseTheme => Theme
- Updated Seeding behavior to only track new elements ([#22](https://github.com/barrelstrength/craft-sprout-import/issues/22)) 
- Updated folder structure
- Moved schema and component definitions to Plugin class
- Moved templates to Sprout Base
- Moved asset bundles to Sprout Base
- Moved craft.sproutImport variable to Sprout Base
- Updated sproutimport_seeds.type => sproutimport_seeds.seedType
- Updated sproutimport_seeds.importerClass => sproutimport_seeds.type

## 1.0.0-beta.7 - 2018-04-17

### Changed
- Updates `league/csv` dependency to `^8.2.0`

## 1.0.0-beta.6 - 2018-04-05

### Fixed
- Fixed invalid query on Weed page for `sproutimport_seeds`
- Fixed icon mask display issue

## 1.0.0-beta.5 - 2018-04-03

## Changed
- Fixed potential conflicts with svg icon styles

## 1.0.0-beta.4 - 2018-03-31

### Fixed
- Fixed registration of Sprout Base

## 1.0.0-beta.3 - 2018-03-30

### Fixed
- Fixed logic around default seed settings

## 1.0.0-beta.2 - 2018-03-26

### Fixed
- Fixed license reference

## 1.0.0-beta.1 - 2018-03-26

### Added
- Initial Craft 3 release

## 0.6.3 - 2018-01-17

### Changed
- Improved examples and documentation

### Fixed
- Fixed issue where Plain text fields with character limits could throw errors

## 0.6.2 - 2017-11-20

### Added
- Added support for seeding Commerce Products with Variants and Commerce Products Relations field
- Added Sprout SEO Redirect Helper tool for converting redirects from a Spreadsheet into JSON

### Changed
- Improved support for Weeding and Keeping Seed data
- Improved performance of generating and saving mock data
- Added `type` and `details` to the Seed log
- Updated Weed log to track Seeded items on a per-import basis
- Improved various example JSON files

### Fixed
- Fixed issue where undefined field variable could occur when using Field Importer
- Fixed issue with default settings for field importer when null
- Fixed issue where Sections without URLs enabled would throw an error
- Fixes bug on weed page where redirect would not happen after successful
- weeding

## 0.5.2 - 2017-01-10

### Added
- Added support for importing Sections with multiple locales

### Changed
- Improved error messages in several scenarios
- Cleaned up syntax in various examples

### Fixed
- Fixed bug where User Import would throw error if not using Craft Pro
- Fixed broken links in UI

## 0.5.1 - 2016-12-05

### Fixed
- Fixed bug where macro was not included on Seed and Weed tab

## 0.5.0 - 2016-10-24

### Added
- Public beta

## 0.4.0 - 2016-01-17

### Added
- Private beta
