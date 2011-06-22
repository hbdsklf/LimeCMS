<?php
class PageTest extends PHPUnit_Framework_TestCase
{
    protected static $em;

    public static function setUpBeforeClass() {
        include('../../config/doctrine.php');
        self::$em = Env::em();
    }

    public function testLoggable() {
        $n = new \Cx\Model\ContentManager\Node();

        $p = new \Cx\Model\ContentManager\Page();

        $p->setLang(1);
        $p->setTitle('testpage');
        $p->setNode($n);

        self::$em->persist($n);

        self::$em->persist($p);

        self::$em->flush();

        //now, create a log
        $p->setTitle('testpage_changed');
        self::$em->persist($p);

        self::$em->flush();

        //now, agiiin
        $p->setTitle('testpage_changed_2');
        self::$em->persist($p);

        self::$em->flush();

        $repo = self::$em->getRepository('Gedmo\Loggable\Entity\LogEntry'); // we use default log entry class

        $this->assertEquals('testpage_changed_2',$p->getTitle());
        $repo->revert($p,1);
        $this->assertEquals('testpage',$p->getTitle());
        $repo->revert($p,2);
        $this->assertEquals('testpage_changed',$p->getTitle());
        $repo->revert($p,1);
        $this->assertEquals('testpage',$p->getTitle());
    }
}