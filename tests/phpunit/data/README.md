# Test Databases

The database dumps are in principle generated in a PHPUnit run of the
cafevdb app and can be copied from [the cafevdb test artifacts
folder](../../../../cafevdb/build/artifacts/tests/phpunit/). However,
the order of generating the PersonalizedFilesView and the
PersonalizedFileDataView has to be interchanged: first the files view
has to be generated, then the file-data view.
