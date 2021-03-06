<?php

namespace Pheasant\Tests\Relationships;

use \Pheasant\Tests\Examples\Hero;
use \Pheasant\Tests\Examples\Power;
use \Pheasant\Tests\Examples\SecretIdentity;

class RelationshipTestCase extends \Pheasant\Tests\MysqlTestCase
{
    public function setUp()
    {
        parent::setUp();

        $migrator = new \Pheasant\Migrate\Migrator();
        $migrator
            ->create('hero', Hero::schema())
            ->create('power', Power::schema())
            ->create('secretidentity', SecretIdentity::schema())
            ;
    }

    public function testOneToManyViaPropertySetting()
    {
        $hero = new Hero(array('alias'=>'Spider Man'));
        $hero->save();
        $this->assertEquals(count($hero->Powers), 0);

        // save via property access
        $power = new Power(array('description'=>'Spider Senses'));
        $power->heroid = $hero->heroid;
        $power->save();
        $this->assertEquals(count($hero->Powers), 1);
        $this->assertTrue($hero->Powers[0]->equals($power));
    }

    public function testOneToManyViaArrayAccess()
    {
        $hero = new Hero(array('alias'=>'Spider Man'));
        $hero->save();
        $this->assertEquals(count($hero->Powers), 0);

        // save via adding
        $power = new Power(array('description'=>'Super-human Strength'));
        $hero->Powers[] = $power;
        $power->save();
        $this->assertEquals(count($hero->Powers), 1);
        $this->assertEquals($power->heroid, 1);
        $this->assertTrue($hero->Powers[0]->equals($power));
    }

    public function testHasOneRelationship()
    {
        $hero = new Hero(array('alias'=>'Spider Man'));
        $hero->save();

        $identity = new SecretIdentity(array('realname'=>'Peter Parker'));
        $identity->Hero = $hero;
        $identity->save();

        $this->assertEquals($hero->identityid, $identity->identityid);
        $this->assertTrue($hero->SecretIdentity->equals($identity));
        $this->assertTrue($identity->Hero->equals($hero));
    }

    public function testPropertyReferencesResolvedInMapping()
    {
        $identity = new SecretIdentity(array('realname'=>'Peter Parker'));
        $hero = new Hero(array('alias'=>'Spider Man'));

        // set the identityid before it's been saved, still null
        $hero->identityid = $identity->identityid;

        $identity->save();
        $hero->save();

        $this->assertEquals($identity->identityid, 1);
        $this->assertEquals($hero->identityid, 1);
    }

    public function testFilteringCollectionsReturnedByRelationships()
    {
        $spiderman = $this->_createHero('Spider Man', 'Peter Parker', array(
            'Super-human Strength', 'Spider Senses'
        ));
        $superman = $this->_createHero('Super Man', 'Clark Kent', array(
            'Super-human Strength', 'Invulnerability'
        ));
        $batman = $this->_createHero('Batman', 'Bruce Wayne', array(
            'Richness', 'Super-human Intellect'
        ));

        $this->assertCount(2, $spiderman->Powers);
        $this->assertCount(1, $spiderman->Powers->filter('description LIKE ?', 'Super-human%')->toArray());
    }

    public function testEmptyRelationships()
    {
        $hero = new Hero(array('alias'=>'Spider Man'));
        $hero->save();

        $this->assertNull($hero->SecretIdentity);
    }

    private function _createHero($alias, $identity, $powers=array())
    {
        $hero = new Hero(array('alias'=>$alias));
        $hero->save();

        $identity = new SecretIdentity(array('realname'=>$identity));
        $hero->SecretIdentity = $identity;
        $identity->save();

        foreach ($powers as $power) {
                $power = new Power(array('description'=>$power));
                $hero->Powers []= $power;
                $power->save();
        }

        return $hero;
    }
}
