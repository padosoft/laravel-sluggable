<?php

namespace Padosoft\Sluggable\Test\Integration;

use Padosoft\Sluggable\InvalidOption;
use Padosoft\Sluggable\SlugOptions;

class HasSlugTest extends TestCase
{
    /** @test */
    public function it_will_save_a_custom_slug_when_saving_a_model()
    {
        $model = new class extends TestModel
        {
            public function getSlugOptions(): SlugOptions
            {
                return parent::getSlugOptions()->generateSlugsFrom('name')->saveCustomSlugsTo('url_custom');
            }
        };
        $model->name = 'hello dad';
        $model->save();
        $this->assertEquals('hello-dad', $model->url);

        $model->url_custom = 'this is a custom test';
        $model->save();
        $this->assertEquals('this-is-a-custom-test', $model->url);
    }

    /**
     * @test
     */
    public function scope_where_slug()
    {
        $model = new class extends TestModel
        {
            public function getSlugOptions(): SlugOptions
            {
                return parent::getSlugOptions()->generateSlugsFrom('name')->saveSlugsTo('url')->saveCustomSlugsTo('url_custom');
            }
        };
        $model->name = 'hello dad';
        $query = $model->whereSlug('hello-dad');
        $bindings = $query->getBindings();
        $this->assertEquals(count($bindings), 1);
        $sql = $query->toSql();
        $this->assertEquals($sql, 'select * from "test_models" where "url" = ?');
    }

    /**
     * @test
     */
    public function scope_find_by_slug_or_fail_ok()
    {
        $model = new class extends TestModel
        {
            public function getSlugOptions(): SlugOptions
            {
                return parent::getSlugOptions()->generateSlugsFrom('name');
            }
        };
        $model->name = 'hello dad';
        $model->save();

        $model2 = TestModel::findBySlugOrFail('hello-dad', ['id']);
        $this->assertEquals($model->id, $model2->id);
    }

    /**
     * @test
     * * @expectedException \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function scope_find_by_slug_or_fail_ko()
    {
        $model = new class extends TestModel
        {
            public function getSlugOptions(): SlugOptions
            {
                return parent::getSlugOptions()->generateSlugsFrom('name');
            }
        };
        $model->name = 'hello dad';
        $model->save();

        TestModel::findBySlugOrFail('hello', ['id']);
    }

    /** @test */
    public function it_will_save_a_slug_when_saving_a_model()
    {
        $model = TestModel::create(['name' => 'this is a test']);

        $this->assertEquals('this-is-a-test', $model->url);
    }

    /** @test */
    public function it_can_handle_null_values_when_creating_slugs()
    {
        $model = new TestModel();
        $model->setSlugOptions($model->getSlugOptions()->allowSlugIfAllSourceFieldsEmpty()->randomSlugsShouldBeNoLongerThan(30));
        $model->name = null;
        $model->save();
        $this->assertEquals(30, strlen($model->url));
    }

    /** @test */
    public function it_will_not_change_the_slug_when_the_source_field_is_not_changed()
    {
        $model = TestModel::create(['name' => 'this is a test']);

        $model->other_field = 'otherValue';
        $model->save();

        $this->assertEquals('this-is-a-test', $model->url);
    }

    /** @test */
    public function it_will_update_the_slug_when_the_source_field_is_changed()
    {
        $model = TestModel::create(['name' => 'this is a test']);

        $model->name = 'this is another test';
        $model->save();

        $this->assertEquals('this-is-another-test', $model->url);
    }

    /** @test */
    public function it_will_update_the_slug_when_the_slug_is_set_to_empty()
    {
        $model = TestModel::create(['name' => 'this is a test']);
        $this->assertEquals('this-is-a-test', $model->url);

        $model->url = '';
        $model->save();
        $this->assertEquals('this-is-a-test', $model->url);
    }

    /** @test */
    public function it_will_not_update_the_slug_when_the_slug_is_already_not_empty()
    {
        $model = TestModel::create(['name' => 'this is a test', 'url' => 'hello']);
        $this->assertEquals('hello', $model->url);
    }

    /** @test */
    public function it_will_save_a_unique_slug_by_default()
    {
        TestModel::create(['name' => 'this is a test']);

        foreach (range(1, 10) as $i) {
            $model = TestModel::create(['name' => 'this is a test']);
            $this->assertEquals("this-is-a-test-{$i}", $model->url);
        }
    }

    /** @test */
    public function it_can_handle_empty_source_fields()
    {
        $model = new TestModel();
        $model->setSlugOptions($model->getSlugOptions()->allowSlugIfAllSourceFieldsEmpty()->randomSlugsShouldBeNoLongerThan(30));
        $model->name = '';
        $model->save();
        $this->assertEquals(30, strlen($model->url));
    }

    /**
     * @test
     * @expectedException \Padosoft\Sluggable\InvalidOption
     */
    public function it_cannot_handle_empty_source_fields()
    {
        $model = new TestModel();
        $model->setSlugOptions($model->getSlugOptions()->disallowSlugIfAllSourceFieldsEmpty());
        $model->name = '';
        $model->save();
    }

    /** @test */
    public function it_can_generate_slugs_from_multiple_source_fields()
    {
        $model = new class extends TestModel
        {
            public function getSlugOptions(): SlugOptions
            {
                return parent::getSlugOptions()->generateSlugsFrom([['name', 'other_field']]);
            }
        };

        $model->name = 'this is a test';
        $model->other_field = 'this is another field';
        $model->save();

        $this->assertEquals('this-is-a-test-this-is-another-field', $model->url);
    }

    /** @test */
    public function it_can_generate_slugs_from_relation_source_fields()
    {
        $modelRelation = TestModelRelation::create(['id' => 1, 'name' => 'relation name']);
        $model = new class extends TestModel
        {
            public function getSlugOptions(): SlugOptions
            {
                return parent::getSlugOptions()->generateSlugsFrom([ ['testmodelrelation.name','name'] ]);
            }

            public function testmodelrelation()
            {
                return $this->belongsTo('\Padosoft\Sluggable\Test\Integration\TestModelRelation', 'testmodelrelation_id');
            }
        };

        $model->name = 'this is a test';
        $model->testmodelrelation_id = 1;
        $model->save();

        $this->assertEquals('relation-name-this-is-a-test', $model->url);
    }

    /** @test */
    public function it_can_generate_slugs_from_a_callable()
    {
        $model = new class extends TestModel
        {
            public function getSlugOptions(): SlugOptions
            {
                return parent::getSlugOptions()->generateSlugsFrom(function (TestModel $model): string {
                    return 'foo-' . str_slug($model->name);
                });
            }
        };

        $model->name = 'this is a test';
        $model->save();

        $this->assertEquals('foo-this-is-a-test', $model->url);
    }

    /** @test */
    public function it_can_generate_duplicate_slugs()
    {
        foreach (range(1, 10) as $i) {
            $model = new class extends TestModel
            {
                public function getSlugOptions(): SlugOptions
                {
                    return parent::getSlugOptions()->allowDuplicateSlugs();
                }
            };

            $model->name = 'this is a test';
            $model->save();

            $this->assertEquals('this-is-a-test', $model->url);
        }
    }

    /** @test */
    public function it_can_generate_slugs_with_a_maximum_length()
    {
        $model = new class extends TestModel
        {
            public function getSlugOptions(): SlugOptions
            {
                return parent::getSlugOptions()->slugsShouldBeNoLongerThan(5);
            }
        };

        $model->name = '123456789';
        $model->save();

        $this->assertEquals('12345', $model->url);
    }

    /**
     * @test
     * @dataProvider weirdCharacterProvider
     */
    public function it_can_handle_weird_characters_when_generating_the_slug(string $weirdCharacter, string $normalCharacter)
    {
        $model = TestModel::create(['name' => $weirdCharacter]);

        $this->assertEquals($normalCharacter, $model->url);
    }

    public function weirdCharacterProvider()
    {
        return [
            ['é', 'e'],
            ['è', 'e'],
            ['à', 'a'],
            ['a€', 'a'],
            ['ß', 'ss'],
            ['a/ ', 'a'],
        ];
    }

    /** @test */
    public function it_can_handle_overwrites_when_updating_a_model()
    {
        $model = TestModel::create(['name' => 'this is a test']);

        $model->url = 'this-is-an-url';
        $model->save();

        $this->assertEquals('this-is-an-url', $model->url);
    }

    /** @test */
    public function it_can_handle_duplicates_when_overwriting_a_slug()
    {
        $model = TestModel::create(['name' => 'this is a test']);
        $other_model = TestModel::create(['name' => 'this is an other']);

        $model->url = 'this-is-an-other';
        $model->save();

        $this->assertEquals('this-is-an-other-1', $model->url);
    }

    /**
     * @test
     */
    public function scope_find_by_slug()
    {
        $model = new class extends TestModel
        {
            public function getSlugOptions(): SlugOptions
            {
                return parent::getSlugOptions()->generateSlugsFrom('name');
            }
        };
        $model->name = 'hello dad';
        $model->save();

        $model2 = TestModel::findBySlug('hello-dad', ['id']);
        $this->assertEquals($model->id, $model2->id);
    }

}
