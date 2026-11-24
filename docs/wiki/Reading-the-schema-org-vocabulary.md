Written against v0.2.1.

A page needs the schema.org types and properties for structured data: what a type is, what it extends, and which properties it takes.

## Steps

1. Read a type. `SchemaType` is backed by the schema.org type name, so `from('Person')` finds `SchemaType::Person`:

   ```php
   use UAMSWeb\Aether\Enums\SchemaType;

   $person = SchemaType::from('Person');

   $person->label();             // "Person"
   $person->toSchemaOrgUrl();    // "https://schema.org/Person"
   $person->typeDescription();   // the schema.org description of the type
   ```

   A type whose name starts with a digit has a case name with a prefix: `SchemaType::Type3DModel` has the value `3DModel`.

2. List the properties of a type, and ask about one:

   ```php
   $person->propertyNames();               // the 81 property names of Person
   $person->hasProperty('name');           // true
   $person->hasProperty('nope');           // false
   $person->propertyDescription('name');   // "The name of the item."
   ```

   `properties()` returns the same names keyed to their descriptions.

3. Walk the type tree with the static helpers, which take a type name:

   ```php
   SchemaType::parentsOf('Person');       // ["Thing"]
   SchemaType::ancestorsOf('Physician');  // ["LocalBusiness", "MedicalBusiness", "MedicalOrganization", "Organization", "Place", "Thing"]
   SchemaType::childrenOf('Physician');
   SchemaType::descendantsOf('Physician');
   SchemaType::parentsOf('NoSuchType');   // [], a name the enum does not have
   ```

   `typeDescriptionFor()` and `propertyDescriptionFor()` do the same job from a name and return `null` for an unknown or `null` type.

4. Read a property on its own. `SchemaProperty` is backed by the property name, and it knows the types it applies to and the values it takes:

   ```php
   use UAMSWeb\Aether\Enums\SchemaProperty;

   $about = SchemaProperty::about;

   $about->domainTypes(); // ["Certification", "CommunicateAction", "CreativeWork", ...]
   $about->rangeTypes();  // ["Role", "Text", "Thing", "URL"]
   $about->supersededBy(); // null, unless schema.org replaced the property
   ```

5. Read the members of an enumeration type. `members()` on an enumeration type, and `membersFor('<type>')`, list its members' names; `SchemaEnumerationMember` holds each one:

   ```php
   SchemaType::membersFor('MusicAlbumReleaseType'); // ["AlbumRelease", "BroadcastRelease", "EPRelease", ...]
   ```

## Reference

- [Schema.org enums](Schema-org-enums.md): every method of the four schema.org enums.
- [Schema.org JSON-LD analyzer](Schema-org-JSON-LD-analyzer.md): the script that reports the vocabulary's structure.
