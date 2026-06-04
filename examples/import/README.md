# Application Import Example

This companion example demonstrates BEAR.Sunday application import without
mixing an artificial feature into the CMS domain.

- Imported app namespace: `MyVendor\Cms\Example\ImportedCatalog`
- Imported host: `catalog`
- Imported resource: `app://catalog/status`
- Import wiring: `BEAR\Package\Module\ImportAppModule`
- Executable proof: `tests/Example/ImportAppExampleTest.php`

The main CMS remains available as `app://self/*` while the companion app is
addressed through its own host. This is the intended shape for composition
examples that are useful for the manual catalog but not natural CMS features.
