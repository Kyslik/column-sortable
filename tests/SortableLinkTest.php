<?php

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Request;
use Kyslik\ColumnSortable\SortableLink;

/**
 * Class SortableLinkTest
 */
class SortableLinkTest extends \Orchestra\Testbench\TestCase
{

    public function testQueryStringParameterWithBooleanStaysInLink()
    {
        $parameters = ['key' => 0, 'another-key' => null, 'another-one' => 1];
        Request::replace($parameters);

        $link     = SortableLink::render(['column']);
        $expected = http_build_query($parameters);

        $this->assertContains($expected, $link);
    }


    public function testQueryStringCanHoldArray()
    {
        $parameters = ['key' => ['part1', 'part2'], 'another-one' => 1];
        Request::replace($parameters);

        $link     = SortableLink::render(['column']);
        $expected = http_build_query($parameters);

        $this->assertContains($expected, $link);
    }


    public function testInjectTitleInQueryStrings()
    {
        Config::set('columnsortable.inject_title_as', 'title');
        SortableLink::render(['column', 'ColumnTitle']);

        $expected = ['title' => 'ColumnTitle'];
        $this->assertEquals($expected, Request::all());
    }


    public function testInjectTitleInQueryStringsIsOff()
    {
        Config::set('columnsortable.inject_title_as', null);
        SortableLink::render(['column', 'ColumnTitle']);

        $this->assertEquals([], Request::all());
    }


    public function testParseParameters()
    {
        $parameters  = ['column'];
        $resultArray = SortableLink::parseParameters($parameters);
        $expected    = ['column', 'column', 'column', [], []];
        $this->assertEquals($expected, $resultArray);

        $parameters  = ['column', 'ColumnTitle'];
        $resultArray = SortableLink::parseParameters($parameters);
        $expected    = ['column', 'column', 'ColumnTitle', [], []];
        $this->assertEquals($expected, $resultArray);

        $parameters  = ['column', 'ColumnTitle', ['world' => 'matrix']];
        $resultArray = SortableLink::parseParameters($parameters);
        $expected    = ['column', 'column', 'ColumnTitle', ['world' => 'matrix'], []];
        $this->assertEquals($expected, $resultArray);

        $parameters  = ['column', 'ColumnTitle', ['world' => 'matrix'], ['white' => 'rabbit']];
        $resultArray = SortableLink::parseParameters($parameters);
        $expected    = ['column', 'column', 'ColumnTitle', ['world' => 'matrix'], ['white' => 'rabbit']];
        $this->assertEquals($expected, $resultArray);

        $parameters  = ['relation.column'];
        $resultArray = SortableLink::parseParameters($parameters);
        $expected    = ['column', 'relation.column', 'column', [], []];
        $this->assertEquals($expected, $resultArray);

        $parameters  = ['relation.column', 'ColumnTitle'];
        $resultArray = SortableLink::parseParameters($parameters);
        $expected    = ['column', 'relation.column', 'ColumnTitle', [], []];
        $this->assertEquals($expected, $resultArray);

        $parameters  = ['relation.column', 'ColumnTitle', ['world' => 'matrix']];
        $resultArray = SortableLink::parseParameters($parameters);
        $expected    = ['column', 'relation.column', 'ColumnTitle', ['world' => 'matrix'], []];
        $this->assertEquals($expected, $resultArray);

        $parameters  = ['relation.column', 'ColumnTitle', ['world' => 'matrix'], ['red' => 'pill']];
        $resultArray = SortableLink::parseParameters($parameters);
        $expected    = ['column', 'relation.column', 'ColumnTitle', ['world' => 'matrix'], ['red' => 'pill']];
        $this->assertEquals($expected, $resultArray);
    }


    public function testGetOneToOneSort()
    {
        $sortParameter = 'relation-name.column';
        $resultArray   = SortableLink::explodeSortParameter($sortParameter);
        $expected      = ['relation-name', 'column'];
        $this->assertEquals($expected, $resultArray);

        $sortParameter = 'column';
        $resultArray   = SortableLink::explodeSortParameter($sortParameter);
        $expected      = [];
        $this->assertEquals($expected, $resultArray);
    }


    /**
     * @expectedException  Kyslik\ColumnSortable\Exceptions\ColumnSortableException
     * @expectedExceptionCode 0
     */
    public function testGetOneToOneSortThrowsException()
    {
        $sortParameter = 'relation-name..column';
        SortableLink::explodeSortParameter($sortParameter);
    }
}
