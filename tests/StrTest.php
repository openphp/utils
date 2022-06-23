<?php

namespace Openphp\Tests;

use Openphp\Str;
use PHPUnit\Framework\TestCase;

class StrTest extends TestCase
{
    public function testCamel()
    {
        $this->assertSame('fooBar', Str::camel('FooBar'));
        $this->assertSame('fooBar', Str::camel('FooBar'));
        $this->assertSame('fooBar', Str::camel('foo_bar'));
        $this->assertSame('fooBar', Str::camel('_foo_bar'));
        $this->assertSame('fooBar', Str::camel('_foo_bar_'));
    }

    public function testStudly()
    {
        $this->assertSame('FooBar', Str::studly('fooBar'));
        $this->assertSame('FooBar', Str::studly('_foo_bar'));
        $this->assertSame('FooBar', Str::studly('_foo_bar_'));
        $this->assertSame('FooBar', Str::studly('_foo_bar_'));
    }

    public function testSnake()
    {
        $this->assertSame('foo-bar', Str::snake('foo-bar'));
        $this->assertSame('foo-_bar', Str::snake('Foo-Bar'));
        $this->assertSame('foo__bar', Str::snake('Foo_Bar'));
        $this->assertSame('żółtałódka', Str::snake('ŻółtaŁódka'));
    }

    public function testTitle()
    {
        $this->assertSame('Welcome Back', Str::title('welcome back'));
    }

    public function testRandom()
    {
        $this->assertIsString(Str::random(10));
    }

    public function testUpper()
    {
        $this->assertSame('USERNAME', Str::upper('username'));
        $this->assertSame('USERNAME', Str::upper('userNaMe'));
    }
}