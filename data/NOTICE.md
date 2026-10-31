# Data Sources

The package code is under the MIT license. The files in this directory keep the licenses of their sources.

The post office files were converted from their source. The commits that added the other files did not record a source, so those sources come from comparing the files with public datasets in November 2026.

## Post offices and postcodes

The files `countries/MYS/states/*/post-offices.json` come from [AsyrafHussin/malaysia-postcodes](https://github.com/AsyrafHussin/malaysia-postcodes), commit `842ca633a72571d8b1cc7e445eefd536cb773f19` (2026-03-06).

They differ from the source in one way: the town that the source spells "Al Muktatfi Billah Shah" is spelled "Al Muktafi Billah Shah" here.

The source's license:

```
MIT License

Copyright (c) 2020-2025 Asyraf Hussin

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
```

## Countries

`countries.json` matches `all/all.json` from [lukes/ISO-3166-Countries-with-Regional-Codes](https://github.com/lukes/ISO-3166-Countries-with-Regional-Codes) by Luke Duncalfe, commit `99cdae15c839bb4f94a23db4e47b4a4c590dec61` (2024-06-19). It has the same 249 rows in the same order.

It differs from the source in one way: it keeps only the `name`, `alpha-2`, and `alpha-3` columns, renamed to `name`, `alpha_2`, and `code`.

`countries.json` is licensed under the [Creative Commons Attribution-ShareAlike 4.0 International License](https://creativecommons.org/licenses/by-sa/4.0/), like its source, and comes with no warranty. If you share an edited copy of this file, share it under the same license.

## States

`countries/MYS/states.json` uses the 16 [ISO 3166-2:MY](https://en.wikipedia.org/wiki/ISO_3166-2:MY) codes, with the short names of the states and federal territories.

## Districts and subdistricts

The `districts.json` files and the Negeri Sembilan `subdistricts.json` files match [lomotech/jajahan](https://github.com/lomotech/jajahan) by Muhammad Syafiq Zainuddin, commit `32eeb1efc3828bafe9b2bf448773913a29a74bab` (2025-05-02). That project takes its data from the Malaysian public sector data dictionary (SDDSA), the Department of Statistics Malaysia, and the Sabah state government.

All 126 districts and all 160 Negeri Sembilan subdistricts use the same codes as the source. They differ from the source in these ways:

- Names are not in upper case.
- 2 district names drop the "&": `Larut Matang` and `Labuk Sugut`.
- 3 federal territory districts drop the "W. P.": `Kuala Lumpur`, `Labuan`, and `Putrajaya`.
- 3 subdistrict names have other spellings: `Bandar Baru Kota Seriemas`, `Gemenceh`, and `Pekan Gemenceh Bahru`.

The source is licensed under the Apache License 2.0. `LICENSE-APACHE-2.0.txt` holds the license text.

The source of the 13 Kemaman subdistricts in `countries/MYS/states/11/districts/03/subdistricts.json` is not known. They use other codes than the source above.
