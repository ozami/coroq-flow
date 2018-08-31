<?php

use Coroq\Flow\MapToFile;

class MapToFileTest extends PHPUnit_Framework_TestCase {
  public function testLoadFileInTheRootDirectory() {
    $map = new MapToFile("path", __DIR__ . "/MapToFile/file-only/dir1/dir2");
    $params = ["path" => "file.php"];
    $result = $map($params);
    $this->assertEquals(["out" => "file"] + $params, $result);
  }

  public function testLoadFileInSubDirectory() {
    $map = new MapToFile("path", __DIR__ . "/MapToFile/file-only");
    $params = ["path" => "/dir1/dir2/file.php"];
    $result = $map($params);
    $this->assertEquals($params + ["out" => "file"], $result);
  }

  public function testLoadHookFromJustOneLevelDeep() {
    $map = new MapToFile("path", __DIR__ . "/MapToFile/has-dir-hooks/dir1/dir2");
    $params = ["path" => "/file.php", "out" => []];
    $result = $map($params);
    $this->assertEquals([
      "out" => [
        "dir2-all-begin",
        "dir2-dir-begin",
        "file",
        "dir2-dir-end",
        "dir2-all-end",
      ],
    ] + $params, $result);
  }

  public function testLoadDirectoryHooksFromDeeplyNestedDirectories() {
    $map = new MapToFile("path", __DIR__ . "/MapToFile/has-dir-hooks");
    $params = ["path" => "/dir1/dir2/file.php", "out" => []];
    $result = $map($params);
    $this->assertEquals([
      "out" => [
        "root-all-begin",
        "dir1-all-begin",
        "dir2-all-begin",
        "dir2-dir-begin",
        "file",
        "dir2-dir-end",
        "dir2-all-end",
        "dir1-all-end",
        "root-all-end",
      ],
    ] + $params, $result);
  }

  /**
   * @expectedException LogicException
   */
  public function testRootDirectoryDoesNotExist() {
    $map = new MapToFile("path", __DIR__ . "/MapToFile/not-exist");
  }

  public function testIntermediateDirectoryDoesNotExist() {
    $map = new MapToFile("path", __DIR__ . "/MapToFile/has-dir-hooks");
    $params = ["path" => "/dir1/not-exist/file.php", "out" => []];
    $result = $map($params);
    $this->assertEquals([
      "out" => [
        "root-all-begin",
        "dir1-all-begin",
        "dir1-all-end",
        "root-all-end",
      ],
    ] + $params, $result);
  }

  public function testFuncFileDoesNotExist() {
    $map = new MapToFile("path", __DIR__ . "/MapToFile/has-dir-hooks");
    $params = ["path" => "/dir1/dir2/not-exist.php", "out" => []];
    $result = $map($params);
    $this->assertEquals([
      "out" => [
        "root-all-begin",
        "dir1-all-begin",
        "dir2-all-begin",
        "dir2-dir-begin",
        "dir2-dir-end",
        "dir2-all-end",
        "dir1-all-end",
        "root-all-end",
      ],
    ] + $params, $result);
  }

  public function testFuncFileInRootDoesNotExist() {
    $map = new MapToFile("path", __DIR__ . "/MapToFile/has-dir-hooks");
    $params = ["path" => "/not-exist.php", "out" => []];
    $result = $map($params);
    $this->assertEquals([
      "out" => [
        "root-all-begin",
        "root-dir-begin",
        "root-dir-end",
        "root-all-end",
      ],
    ] + $params, $result);
  }

  public function testRequestForSpecialFileNamesAreIgnored() {
    $map = new MapToFile("path", __DIR__ . "/MapToFile/has-dir-hooks");
    $params = ["path" => "/__dir__.php", "out" => []];
    $result = $map($params);
    $this->assertEquals($params, $result);

    $params = ["path" => "/dir1/__dir__.php", "out" => []];
    $result = $map($params);
    $this->assertEquals($params, $result);

    $params = ["path" => "/__all__.php", "out" => []];
    $result = $map($params);
    $this->assertEquals($params, $result);

    $params = ["path" => "/dir1/__all__.php", "out" => []];
    $result = $map($params);
    $this->assertEquals($params, $result);
  }

  /**
   * @expectedException DomainException
   */
  public function testLoadFunctionFromFileThrowsExceptionWhenTheFileDidNotReturnCallable() {
    $map = new MapToFile("path", __DIR__ . "/MapToFile/");
    $map->loadFunctionFromFile(__DIR__ . "/MapToFile/return_nothing.php");
  }

  public function testResolveDots() {
    $map = new MapToFile("path", __DIR__ . "/MapToFile/");
    $this->assertSame(
      $map->resolveDots("//test1//test2//test3///..//"),
      "test1/test2"
    );
    $this->assertSame(
      $map->resolveDots("././test1/.test2/././"),
      "test1/.test2"
    );
    $this->assertSame(
      $map->resolveDots("../test1/..test2/test3/.."),
      "test1/..test2"
    );
    $this->assertSame(
      $map->resolveDots(".//..//test1//test2/./..//.//test3//"),
      "test1/test3"
    );
  }
}
