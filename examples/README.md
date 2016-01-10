# Sprout Import Example Files

These files are meant to be a starting point for importing content. Many of them require that you update some details before you will get them working on your specific Craft site.

For example, sectionIds and userIds may be different for your installation and you will need to update the example files to use ids that match your content.

## Seed Examples

The examples in the `examples/seed` folder are base templates that can generate 100s or 1000s of fake entries. They require you install a separate plugin called Faker:
https://github.com/sjelfull/Craft-Faker

Place these examples in your `craft/templates` folder and visit the front-end URL where they display to download a copy of the file to import.

## Third-party Integrations

Some examples in this folder require the third-party plugins they are associated with to work.  For example, the `elements-sproutforms-entries.json` example would require you have Sprout Forms installed and a Form with the appropriate `formId` and fields.

