Written against v0.2.1.

An upload check, a download header or a file icon needs the MIME type for a file extension, or the extensions that a MIME type string allows.

## Steps

1. Find a case by file extension. The match ignores case, and a leading dot is accepted:

   ```php
   use UAMSWeb\Aether\Enums\MimeType;

   $type = MimeType::findByExtension('jpg');   // MimeType::IMAGE_JPEG
   $type->mimeType();                          // "image/jpeg"
   ```

   `findByExtension('JPG')` and `findByExtension('.jpg')` find the same case. An extension no case lists, such as `nosuchext`, returns `null`.

2. Find a case by MIME type string. This match is exact and case-sensitive:

   ```php
   MimeType::findByMimeType('image/png');   // MimeType::IMAGE_PNG
   MimeType::findByMimeType('IMAGE/PNG');   // null
   ```

3. Read the extensions a case allows:

   ```php
   $pdf = MimeType::findByMimeType('application/pdf');

   $pdf->extensions();        // ["pdf"]
   $pdf->primaryExtension();  // "pdf"
   $pdf->category();          // "application"
   ```

4. List by category when a field accepts a family of types:

   ```php
   MimeType::getCategories();                        // ["application", "audio", "chemical", "font", ...]
   MimeType::findByCategory('font');                 // every font type (6 cases)
   MimeType::findByCategories(['font', 'model']);    // the two families together (48 cases)
   ```

**Warning:** `MimeType::from('application/pdf')` throws a `ValueError`. The enum is backed by its case names (`APPLICATION_PDF`), so go through `findByMimeType()` for a type string.

## Reference

- [Currency, MIME type and month enums](Currency-MIME-type-and-month-enums.md): every `MimeType` method and the category list.
