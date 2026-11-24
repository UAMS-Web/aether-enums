Written against v0.2.1.

A form asks for a field of study or an occupation in steps (a broad group, then a narrower one), and the site needs the codes above and below a selection.

`IscedA2011`, `IscedP2011`, `IscedF2013` and `Isco08` are hierarchies. Each case is backed by its code, and every case reports its place in the tree the same way.

## Steps

1. Read a case and its place in the tree:

   ```php
   use UAMSWeb\Aether\Enums\IscedF2013;

   $biology = IscedF2013::from('0511');

   $biology->label();     // "Biology"
   $biology->parent();    // "051"
   $biology->ancestors(); // ["05", "051"], broadest first
   $biology->tier();      // 3
   ```

   A top-level code has no parent: `IscedF2013::from('05')->parent()` is `null`, its `ancestors()` is `[]` and its `tier()` is `1`.

2. Go down the tree. `children()` lists the codes one level below, and `descendants()` lists every code below:

   ```php
   $natural = IscedF2013::from('05');

   $natural->children();          // ["050", "051", "052", "053", "054", "058", "059"]
   count($natural->descendants()); // 26
   ```

3. Show the path as text. `labelsWithAncestors()` returns the labels from the broadest ancestor to the case, as a list, or as a string when a separator is given:

   ```php
   $biology->labelsWithAncestors();
   // ["Natural sciences, mathematics and statistics", "Biological and related sciences", "Biology"]

   $biology->labelsWithAncestors(' > ');
   // "Natural sciences, mathematics and statistics > Biological and related sciences > Biology"
   ```

4. For ISCO-08 (`Isco08`), read the structure groups and the title. Codes are one to four digits, and `level()` says how many:

   ```php
   use UAMSWeb\Aether\Enums\Isco08;

   $doctor = Isco08::from('2211');

   $doctor->title();      // "Generalist Medical Practitioners"
   $doctor->level();      // 4
   $doctor->ancestors();  // ["2", "22", "221"]
   $doctor->majorGroup(); // "2"
   ```

5. Build cascading selects from `Isco08::hierarchyOptionRows()`. It returns `majors` (the ten level-1 groups, each a row with `key`, `value` as `"{code} {title}"` and `description`) and `children_by_parent` (the rows of each code's direct children, keyed by the parent's code).

6. Turn a selection back into stored tiers and read the deepest one a person reached:

   ```php
   Isco08::tierStoragePayloadForCode('2211');
   // ['major_group' => '2', 'sub_major_group' => '22', 'minor_group' => '221', 'unit_group' => '2211']

   Isco08::tierStoragePayloadForCode('99999'); // null, an unknown code

   Isco08::resolvedDeepestStructureCode(['major_group' => '2', 'sub_major_group' => '22', 'minor_group' => '221']);
   // "221"
   ```

   `resolvedDeepestStructureCode()` stops at the first empty tier, so a map with an empty `minor_group` and a filled `unit_group` resolves to the `sub_major_group` code.

## Reference

- [Classification enums](Classification-enums.md): every getter of the ISCED and ISCO-08 enums, with their case counts.
