<?php

class IniMLTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->iniML = new IniML();
    }

    public function testUsesColonAsTheKeyValueDelimiter()
    {
        $this->assertEquals(['key' => 'value'],
                            $this->iniML->parse("key: value"));
        $this->assertNotEquals(['key' => 'value'],
                            $this->iniML->parse("key = value"));
    }

    public function testSpaceAroundKeyValueIsIgnored()
    {
        $this->assertEquals(['key' => 'value'],
                            $this->iniML->parse("key:value"));
        $this->assertEquals(['key' => 'value'],
                            $this->iniML->parse("  key  :   value  "));
    }

    public function testAKeyIsAnyNonWhiteCharacters()
    {
        $this->assertEquals(['key.foo.bar' => 42],
                            $this->iniML->parse("key.foo.bar: 42"));
        $this->assertEquals(['f$%&\\!' => 42],
                            $this->iniML->parse("f$%&\\!: 42"));
        $this->assertNotEquals(['a b' => 42],
                            $this->iniML->parse("a b: 42"));
    }

    public function testADuplicateKeyCreatesAList()
    {
        $this->assertEquals(
            [ [ 'name' => 'frank', 'age' => 52 ],
              [ 'name' => 'vincent', 'age' => 64 ] ],
            $this->iniML->parse('name: frank
age: 52
name: vincent
age: 64')
        );
    }

    public function testLinesNotMatchingKeyValueAreKept()
    {
        $this->assertEquals(
            [ 'milk', 'cereals' ],
            $this->iniML->parse('milk
cereals')
        );
    }

    public function testLeadingBackslashEscapes()
    {
        $this->assertEquals(
            [ 'key: value' ],
            $this->iniML->parse('\\key: value')
        );
        $this->assertEquals(
            [ 'key \\ value' ],
            $this->iniML->parse('key \\ value')
        );
        $this->assertEquals(
            [ 'key\\' => 'value' ],
            $this->iniML->parse('key\\: value')
        );
        $this->assertEquals(
            [ 'hello' ],
            $this->iniML->parse('\\hello')
        );
    }

    public function testOneLevelGroupingUsingSections()
    {
        $this->assertEquals(
            [ 'groceries' => [ 'milk', 'cereals' ] ],
            $this->iniML->parse('[groceries]
milk
cereals')
        );
    }

    public function testAnyCharactersIsAllowedAsSectionName()
    {
        $this->assertEquals(
            [ 'all groceries' => [ 'milk', 'cereals' ] ],
            $this->iniML->parse('[  all groceries ]
milk
cereals')
        );
        $this->assertEquals(
            [ '*[1964].txt' => [ 'indent_style' => 'space', 'indent_size' => 2 ] ],
            $this->iniML->parse('[ *[1964].txt ]
indent_style: space
indent_size: 2')
        );
    }

    public function testSectionNameThatSingularizeAreTreatedAsList()
    {
        $this->assertEquals(
            [ 'groceries' => [ [ 'name' => 'milk' ] ] ],
            $this->iniML->parse('[groceries]
name: milk')
        );
        $this->assertEquals(
            [ 'grocery' => [ 'name' => 'milk' ] ],
            $this->iniML->parse('[grocery]
name: milk')
        );
    }

    public function testMultilineStartsWithAnEmptyValue()
    {
        $this->assertEquals(
            [ 'title' => 'Funky',
              'content' => 'Something along
the lines'
            ],
            $this->iniML->parse('title: Funky
content:
Something along
the lines')
        );
    }

    public function testMultilineEndsWithAKeyValue()
    {
        $this->assertEquals(
            [ 'content' => 'Something along
the lines
',
              'name' => 'Wut'
            ],
            $this->iniML->parse('content:
Something along
the lines
name: Wut')
        );
        $this->assertEquals(
            [ 'content' => '',
              'foo' => 'bar'
            ],
            $this->iniML->parse('content:
foo: bar')
        );
    }

    public function testMultilineEndsWithASection()
    {
        $this->assertEquals(
            [ 'content' => 'Something along
the lines
',
              'people' => []
            ],
            $this->iniML->parse('content:
Something along
the lines
[people]')
        );

        $this->assertEquals(
            [ 'content' => '',
              'people' => []
            ],
            $this->iniML->parse('content:
[people]')
        );
    }

    public function testMultilineIndentEmbedsText()
    {
        $this->assertEquals(
            [ 'content' => 'foo: bar
  [sec] !
\\three
',
              'four',
              'key' => 'value' ],
            $this->iniML->parse('content:
  foo: bar
    [sec] !
  \\three
four
key:value
')
        );
    }

    public function testEmitSection()
    {
        $input = "[groceries]\nmilk\napples\nkey: value\n";
        $this->assertSame($input,
                          $this->iniML->emit($this->iniML->parse($input)));
    }

    public function testEmitEscape()
    {
        $input = "\\key: value\n";
        $this->assertSame($input,
                          $this->iniML->emit($this->iniML->parse($input)));
    }

    public function testEmitBoolean()
    {
        $this->assertSame("enable: false\n",
                          $this->iniML->emit([ 'enable' => false ]));
    }

    public function testEmitNull()
    {
        $this->assertSame("enable: null\n",
                          $this->iniML->emit([ 'enable' => null ]));
    }

    public function testEmitNormalizeKeyValue()
    {
        $input = "  foo : bar  ";
        $this->assertEquals("foo: bar\n",
                            $this->iniML->emit($this->iniML->parse($input)));
    }

    public function testOptionIgnoreBlankLines()
    {
        $this->iniML->options['ignoreBlankLines'] = false;
        $this->assertEquals(
            ['', 'a', 'b', '', 'c'],
            $this->iniML->parse("
a
b

c")
        );
    }

}
