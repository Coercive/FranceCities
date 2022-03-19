# FranceCities
List of France cities with zip codes, department codes, region codes, districts for former cities, GPS latitude longitude coordinates, and INSEE reference code.

## Foreword
The approach comes from the author **Thomas Loiret**, who offers on the following link reliable sources and a way to compile them. [https://b0uh.github.io/how-to-get-the-list-of-french-cities-and-more.html]

## Sources

### LA POSTE - Zip codes and GPS coordinates
Retrieve the CSV file from La Poste for postal codes and GPS coordinates. [https://datanova.legroupe.laposte.fr/explore/dataset/laposte_hexasmal/export/?disjunctive.code_commune_insee&disjunctive.nom_de_la_commune&disjunctive.code_postal&disjunctive.ligne_5]

### INSSEE - List of french cities
Retrieve INSEE Excel data for the list of french cities. Communal division - Table of geographical affiliation of the municipalities. [https://www.insee.fr/fr/information/2028028]

## Convert XLSX
First, the INSEE .xlsx file must be converted. It is necessary to recover the first sheet "COM" (List of municipalities) and the second sheet "ARM" (Municipal districts).

You can use external software to do this, or use phpoffice/phpspreadsheet directly in php in the same script. [https://github.com/PHPOffice/PhpSpreadsheet]

```php
<?php
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PhpOffice\PhpSpreadsheet\Writer\Csv;

# INSEE .xlsx filepath
$source = '/table-appartenance-geo-communes.xlsx';

# Sheet 1 : [COM] Communes (List of municipalities)
$insee_com = '/insee_com.csv';

# Load Excel sheet and Transform
$reader = new Xlsx;
$reader->setReadDataOnly(true); # do not convert to integer
$sheet = $reader->load($source);
$writer = new Csv($sheet);
$writer->setDelimiter(';');
$writer->setSheetIndex(0); # select first sheet
$writer->save($insee_com);

# Sheet 2 : [ARM] Arrondissements (Municipal districts)
$insee_arm = '/insee_arm.csv';

# Load Excel sheet and Transform
$reader = new Xlsx;
$reader->setReadDataOnly(true); # do not convert to integer
$sheet = $reader->load($source);
$writer = new Csv($sheet);
$writer->setDelimiter(';');
$writer->setSheetIndex(1); # select second sheet
$writer->save($insee_arm);
```

## Read LA POSTE data .csv
Get only the necessary fields, and convert the csv to a php array.

```php
<?php
use Coercive\Utility\Csv\Importer;

# Delimiter ;
# Columns : Code_commune_INSEE;Nom_commune;Code_postal;Ligne_5;Libellé_d_acheminement;coordonnees_gps
$source = '/laposte_hexasmal.csv';

$importer = new Importer($source, ';');
$importer->parseHeader();
$importer->onlyHeader([
  'Code_commune_INSEE',
  'Nom_commune',
  'Code_postal',
  'coordonnees_gps',
]);
$laposte_data = $importer->get();
```

## Read INSEE COM data .csv (sheet 1)
Get only the necessary fields, and convert the csv to a php array.

```php
<?php
use Coercive\Utility\Csv\Importer;

# Sheet 1 : [COM] Communes (List of municipalities)
$insee_com = '/insee_com.csv';

$importer = new Importer($insee_com, ';');
$importer->seek(5); # skip introduction paragraphs
$importer->parseHeader(false);
$importer->onlyHeader([
  'CODGEO',
  'LIBGEO',
  'DEP',
  'REG',
]);
$insee_com_data = $importer->get();
```

## Read INSEE ARM data .csv (sheet 2)
Get only the necessary fields, and convert the csv to a php array.

```php
<?php
use Coercive\Utility\Csv\Importer;

# Sheet 2 : [ARM] Arrondissements (Municipal districts)
$insee_arm = '/insee_arm.csv';

$importer = new Importer($insee_arm, ';');
$importer->seek(5); # skip introduction paragraphs
$importer->parseHeader(false);
$importer->onlyHeader([
  'CODGEO',
  'LIBGEO',
  'DEP',
  'REG',
  'COM', # Communes englobantes (surrounding municipalities)
]);
$insee_arm_data = $importer->get();
```
