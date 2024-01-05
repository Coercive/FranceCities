# France Cities
List of France cities with zip codes, department codes, region codes, districts for former cities, GPS latitude longitude coordinates, and INSEE reference code.

***
## 2023 - French cities and overseas collectivities
There are **37,563** cities and associated or delegated municipalities in France on January 1, 2024, to which are included **45** city districts**

There are **94** french overseas collectivities on March 1, 2017.

Total of **37,657** french municipalities.

2023 data files are available in **2023/2023_compiled_data** directory *(CSV, SQL, JSON)* https://github.com/Coercive/FranceCities/tree/main/2023/2023_compiled_data

> **NEW** : all data are merged into one database. Some new fields allow you to separate them, like : **ASSOCIATED_MUNICIPALITY**, **DELEGATED_MUNICIPALITY**, **OVERSEA_MUNICIPALITY**, **IS_DISTRICT**

> **LIGHT VERSION** (no GPS Geometry) https://github.com/Coercive/FranceCities/tree/main/2023/2023_compiled_data_light

***
## 2022 - French cities
There are **34,955** cities in France on January 1, 2022, to which are added **45** city districts, for a total of **35,000 entries**

2022 data files are available in **2022_datas** directory *(CSV, CSV for MS Excel, SQL, XLSX, YAML)* [https://github.com/Coercive/FranceCities/tree/main/2022_france_cities]

***
## 2022 - French overseas collectivities
There are **94** french overseas collectivities on March 1, 2017.

2022 data files are available in **overseas_collectivities** directory *(CSV, CSV for MS Excel, SQL, XLSX, YAML)* [https://github.com/Coercive/FranceCities/tree/main/2022_overseas_collectivities]

***
## !! Monaco (ref-99138)

Please note that **98000 Monaco** is NOT integrated to Coercive/FrenchCities.
> The only one entry in La Poste Datanova is **99138;MONACO;98000;;MONACO;43.7384176,7.4246158**

Monaco postal codes are integrated into the French postal system. They begin with "980", and have five digits, in the form "980XX", like French postal codes. The most common postcode in Monaco, excluding CEDEX and special addresses, is therefore postcode 98000.

Source **INSEE** https://www.insee.fr/fr/metadonnees/cog/pays/PAYS99138-monaco

Source **Wikipedia** https://fr.wikipedia.org/wiki/Monaco

***
## Foreword
The approach comes from the author **Thomas Loiret**, who offers on the following link reliable sources and a way to compile them. https://b0uh.github.io/how-to-get-the-list-of-french-cities-and-more.html

***
## Sources 2023

### LA POSTE - Zip codes and GPS coordinates
Retrieve the CSV file from La Poste for postal codes and GPS coordinates. https://datanova.laposte.fr/datasets/laposte-hexasmal

### INSSEE - List of french cities
Retrieve INSEE CSV data for the list of french cities. https://www.insee.fr/fr/information/6800675

### INSSEE - List of overseas collectivities
Retrieve the CSV file from here https://www.insee.fr/fr/information/6800675

***
## Sources 2022

### LA POSTE - Zip codes and GPS coordinates
Retrieve the CSV file from La Poste for postal codes and GPS coordinates. https://datanova.legroupe.laposte.fr/explore/dataset/laposte_hexasmal/export/?disjunctive.code_commune_insee&disjunctive.nom_de_la_commune&disjunctive.code_postal&disjunctive.ligne_5

### INSSEE - List of french cities
Retrieve INSEE Excel data for the list of french cities. Communal division - Table of geographical affiliation of the municipalities. https://www.insee.fr/fr/information/2028028

### INSSEE - List of overseas collectivities
Retrieve manually municipalities lists from here https://www.insee.fr/fr/information/2028040

***
## Scripts process
Code examples and explanations are available in the **2022_france_cities** and **2022_overseas_collectivities** folders. *(readme and script files inside)*