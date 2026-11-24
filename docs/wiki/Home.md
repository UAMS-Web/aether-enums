Written against v0.2.1.

Aether Enums holds the twenty reference-data enums that UAMS Statamic sites and the fieldtype add-on share (countries, currencies, languages, U.S. states, counties and ZIP codes, MIME types, schema.org vocabularies and several standard classifications), and the nine `generate:*` commands that rebuild them from their sources. It has no Control Panel screens, settings, routes, tags, permissions or fieldtypes: installing it makes the enums and the commands available.

This wiki holds the documentation: walkthroughs and task guides for using the enums, the reference for every enum and command, and troubleshooting. The [README](https://github.com/UAMS-Web/aether-enums#readme) keeps the overview, the requirements, the installation and the table of enums with their case counts.

## Getting started

- [Installation (README)](https://github.com/UAMS-Web/aether-enums#installation): add the package to a Statamic install.
- [Your first lookup](Your-first-lookup.md): read a state, a country and a currency, and see how a case value differs from its label.

## Guides

- [Building a select list](Building-a-select-list.md): turn an enum into the options of a form field.
- [Finding a MIME type](Finding-a-MIME-type.md): look a type up by its string, a file extension or a category.
- [Walking a classification hierarchy](Walking-a-classification-hierarchy.md): move up and down ISCED and ISCO-08 codes.
- [Reading the schema.org vocabulary](Reading-the-schema-org-vocabulary.md): types, properties and their descriptions.
- [Regenerating an enum](Regenerating-an-enum.md): run a generator from a checkout and review the result.
- [Warming a host cache](Warming-a-host-cache.md): hook a host application into `generate:schema-type-enum`.

## Troubleshooting

- [Troubleshooting](Troubleshooting.md): one entry per symptom, with its cause and fix.

## Reference

- [Reference](Reference.md): what the add-on registers, and the index of the reference pages.
- [Geography enums](Geography-enums.md): `State`, `CanadianProvince`, `Country`, `County` and `ZipCode`.
- [Language enums](Language-enums.md): `Language` and `LanguageBCP47`.
- [Classification enums](Classification-enums.md): `IscedA2011`, `IscedP2011`, `IscedF2013`, `Isco08`, `CredentialRegistry` and `InternationalCodeDesignator`.
- [Schema.org enums](Schema-org-enums.md): `SchemaType`, `SchemaProperty`, `SchemaDataType` and `SchemaEnumerationMember`.
- [Currency, MIME type and month enums](Currency-MIME-type-and-month-enums.md): `Currency`, `MimeType` and `ApStyleGregorianMonth`.
- [Metadata attributes](Metadata-attributes.md): the `Info` classes and `EnumDataUtils`.
- [Generator commands](Generator-commands.md): the nine commands, their options, outputs and requirements.
- [Schema.org JSON-LD analyzer](Schema-org-JSON-LD-analyzer.md): the script that reports the structure of the vocabulary.

## Project

- [Project](Project.md): where the add-on is developed and how to report a problem.
