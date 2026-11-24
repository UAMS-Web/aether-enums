Written against v0.2.1.

`Language` lists ISO 639 languages and `LanguageBCP47` lists BCP 47 language tags, both in the `UAMSWeb\Aether\Enums` namespace.

| Enum | Cases | Backing value (what `from()` takes) | Example |
| --- | --- | --- | --- |
| `Language` | 8,037 | The ISO 639-3 code | `Language::from('eng')` |
| `LanguageBCP47` | 8,268 | The BCP 47 subtag | `LanguageBCP47::from('en')` |

Neither enum is a candidate for a dropdown of every case; filter by `type()` or `scope()` first, or look a code up with `from()` or `tryFrom()`.

## Language

| Method | Returns | Example for `Language::eng` |
| --- | --- | --- |
| `name()` | `string`, the English name | `English` |
| `scope()` | `string` | `Individual` |
| `type()` | `string` | `Living` |
| `iso639_1()` | `string`, the two-letter code | `en` |
| `iso639_2_b()` | `string`, the bibliographic three-letter code | `eng` |
| `iso639_2_t()` | `string`, the terminologic three-letter code | `eng` |
| `iso639_3()` | `string` | `eng` |
| `iso639_5()` | `string`, the ISO 639-5 code | throws `ValueError` for this case |
| `alternate_names()` | `array<int, string>` | `["anglais", "Englisch"]` |
| `Language::fieldValueForLanguageCode($code, $field)` | `string`, `array` or `null`: one field for a code, `null` when the code is unknown or the field has no value | `fieldValueForLanguageCode('eng', 'name')` is `English` |

**Warning:** the five code getters (`iso639_1()`, `iso639_2_b()`, `iso639_2_t()`, `iso639_3()`, `iso639_5()`) throw a `ValueError` (`Unknown field 'iso639_1' for language: aaa`) for a case that has no such code, and most cases lack some of them: measured over all 8,037 cases, `iso639_1()` throws for 7,853, `iso639_2_b()` and `iso639_2_t()` for 7,552, `iso639_3()` for 113 and `iso639_5()` for 7,924. This is tracked in [#66](https://github.com/UAMS-Web/aether-enums/issues/66). Until it is decided, read an optional code through `Language::fieldValueForLanguageCode($code, 'iso639_1')`, which returns `null` instead of throwing, or catch the `ValueError`. `name()`, `scope()`, `type()` and `alternate_names()` are not affected.

## LanguageBCP47

The registry's optional fields return `null` when a tag has none.

| Method | Returns | Example for `LanguageBCP47::from('en')` |
| --- | --- | --- |
| `type()` | `string`, such as `language` | `language` |
| `description()` | `string` | `English` |
| `added()` | `string`, the date the subtag was added | `2005-10-16` |
| `scope()` | `?string` | `null` |
| `comments()` | `?string` | `null` |
| `macrolanguage()` | `?string` | `null` |
| `deprecated()` | `?string`, the deprecation date | `null` |
| `preferredValue()` | `?string`, the replacement for a deprecated tag | `null` |
| `suppressScript()` | `?string`, the script a tag omits by default | `Latn` |
| `LanguageBCP47::fieldValueForLanguageCode($code, $field)` | `string`, `array` or `null`: one field for a subtag, `null` when unknown | `fieldValueForLanguageCode('en', 'description')` is `English` |

## See also

- [Metadata attributes](Metadata-attributes.md): `LanguageInfo` and `LanguageBCP47Info` hold every field.
- [Troubleshooting](Troubleshooting.md): the `Unknown field` error.
