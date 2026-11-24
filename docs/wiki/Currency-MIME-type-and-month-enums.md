Written against v0.2.1.

Three enums cover currencies, MIME types and Gregorian months, all in the `UAMSWeb\Aether\Enums` namespace.

| Enum | Cases | Backing value (what `from()` takes) | Example |
| --- | --- | --- | --- |
| `Currency` | 154 | The ISO 4217 code | `Currency::from('JPY')` |
| `MimeType` | 2,564 | The case name (`IMAGE_PNG`) | `MimeType::from('IMAGE_PNG')` |
| `ApStyleGregorianMonth` | 12 | The full English month name | `ApStyleGregorianMonth::from('September')` |

## Currency

| Method | Returns | Example for `Currency::EUR` |
| --- | --- | --- |
| `name()` | `string`, the English name | `Euro`; `Currency::JPY->name()` is `Yen` |
| `numeric()` | `string`, the ISO 4217 numeric code | |
| `symbol()` | `string` | `€` |
| `symbol_placement()` | `string`: `before`, `after` or `between`, where the symbol sits relative to the amount | `before` |
| `symbol_separation()` | `bool`, whether a space separates the symbol from the amount | `true` |
| `minor_unit()` | `int`, the number of decimal places of the minor unit | `2`; `Currency::JPY->minor_unit()` is `0` |
| `symbol_with_separator()` | `string`, the symbol with a space added on the side the separation puts it, or the bare symbol when there is no separation | ` €` (a space, then the symbol) |

`Currency::USD->symbol_with_separator()` is `$`, with no space, because `USD` has no separation.

## MimeType

The backing value is the case name, so `MimeType::from('application/pdf')` throws a `ValueError`. Look a type up with the finders below.

| Method | Returns | Example |
| --- | --- | --- |
| `mimeType()` | `string`, the type string | `MimeType::IMAGE_PNG->mimeType()` is `image/png` |
| `extensions()` | `array<int, string>`, the file extensions | `["pdf"]` for `application/pdf` |
| `primaryExtension()` | `?string`, the first extension, or `null` | `pdf` |
| `category()` | `string`, the part of the type before the slash | `application` |
| `MimeType::findByMimeType($type)` | `?self`, an exact, case-sensitive match | `findByMimeType('image/png')` is `IMAGE_PNG`; `'IMAGE/PNG'` is `null` |
| `MimeType::findByExtension($ext)` | `?self`; ignores case and accepts a leading dot | `findByExtension('JPG')` and `findByExtension('.jpg')` are `IMAGE_JPEG` |
| `MimeType::findByCategory($category)` | `array<int, self>` | 6 cases for `font` |
| `MimeType::findByCategories($categories)` | `array<int, self>` | 48 cases for `['font', 'model']` |
| `MimeType::getCategories()` | `array<int, string>` | `application`, `audio`, `chemical`, `font`, `haptics`, `image`, `message`, `model`, `multipart`, `text`, `video`, `x-conference`, `x-shader` |

A walkthrough is in [Finding a MIME type](Finding-a-MIME-type.md).

## ApStyleGregorianMonth

Month labels that follow the AP Stylebook. Backing values are the full English names, matching Carbon's `format('F')` in the default locale.

| Method | Returns | Example |
| --- | --- | --- |
| `ApStyleGregorianMonth::fromMonthNumber(int $month)` | `?self`, for 1 (January) to 12 (December), otherwise `null` | `fromMonthNumber(9)` is `September`; `fromMonthNumber(13)` is `null` |
| `proseLabel()` | `string`, the running-text abbreviation | `Sept.` for September |
| `tabularLabel()` | `string`, the three-letter label with no period | `Sep` for September |
| `spelledOutName()` | `string`, the full name | `September` |
| `runningTextLabel(bool $includesSpecificDay)` | `string`, abbreviated when a day number appears and spelled out otherwise | `ApStyleGregorianMonth::August->runningTextLabel(true)` is `Aug.`, and `(false)` is `August` |
| `labelForTabularMaterial(bool $tabularMaterial)` | `string`, the tabular label when `true`, the prose label when `false` | `May` for May with `true`; `March` for March with `false` |

## See also

- [Metadata attributes](Metadata-attributes.md): `CurrencyInfo`, `MimeTypeInfo` and `ApStyleMonthInfo`.
