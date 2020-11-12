<?php
namespace ExpressRequest\Filters;



use Cake\Database\Expression\QueryExpression;
use Cake\Datasource\QueryInterface;
use Cake\TestSuite\TestCase;

class SearchDateFilterTest extends TestCase
{

    public function testFactoryByYearString()
    {
        $queryMock = $this->createMock(QueryInterface::class);
        $searchFilter = new SearchDateFilter('date');
        $searchFilter->setValue('2020');
        $this->assertInstanceOf(\DateTime::class, $searchFilter->getValue());
        $this->assertInstanceOf(
            QueryExpression::class,
            $searchFilter->process(new QueryExpression(), 'test', $queryMock)
        );
        $this->assertEquals('2020', $searchFilter->getValue()->format('Y'));
    }

    public function testFactoryByYearAndMonthString()
    {
        $queryMock = $this->createMock(QueryInterface::class);
        $searchFilter = new SearchDateFilter('date');
        $searchFilter->setValue('2020-03');
        $this->assertInstanceOf(\DateTime::class, $searchFilter->getValue());
        $this->assertInstanceOf(
            QueryExpression::class,
            $searchFilter->process(new QueryExpression(), 'test', $queryMock)
        );
        /** @var \DateTime $date */
        $date = $searchFilter->getValue();
        $this->assertEquals('2020', $date->format('Y'), 'Year is 2020.');
        $this->assertEquals('Mar', $date->format('M'), 'Month is marc');
    }

    public function testFactoryByYearAndMonthAndDayString()
    {
        $queryMock = $this->createMock(QueryInterface::class);
        $searchFilter = new SearchDateFilter('date');
        $searchFilter->setValue('2020-03-03');
        $this->assertInstanceOf(\DateTime::class, $searchFilter->getValue());
        $this->assertInstanceOf(
            QueryExpression::class,
            $searchFilter->process(new QueryExpression(), 'test', $queryMock)
        );
        /** @var \DateTime $date */
        $date = $searchFilter->getValue();
        $this->assertEquals('2020', $date->format('Y'), 'Year is 2020.');
        $this->assertEquals('Mar', $date->format('M'), 'Month is marc');
        $this->assertEquals('03', $date->format('d'), 'Day is 03');
    }

    public function testFactoryByArrayOperators()
    {
        $queryMock = $this->createMock(QueryInterface::class);
        $searchFilter = new SearchDateFilter('date');
        $searchFilter->setValue(['gte' => '2020-03-03', 'lte' => '2020-04-03']);
        $values = $searchFilter->getValue();
        $this->assertCount(2, $values);
        $this->assertInstanceOf(\DateTime::class, $values[0]);
        $this->assertInstanceOf(\DateTime::class, $values[1]);
        $this->assertInstanceOf(
            QueryExpression::class,
            $searchFilter->process(new QueryExpression(), 'test', $queryMock)
        );

        /** Test first date */
        /** @var \DateTime $date */
        $date = $values[0];
        $this->assertEquals('2020', $date->format('Y'), 'Year is 2020.');
        $this->assertEquals('Mar', $date->format('M'), 'Month is marc');
        $this->assertEquals('03', $date->format('d'), 'Day is 03');

        /** Test secondDate date */
        /** @var \DateTime $dateTwo */
        $dateTwo = $values[1];
        $this->assertEquals('2020', $dateTwo->format('Y'), 'Year is 2020.');
        $this->assertEquals('Apr', $dateTwo->format('M'), 'Month is marc');
        $this->assertEquals('03', $dateTwo->format('d'), 'Day is 03');
    }

    public function testFactoryByArrayOperator()
    {
        $queryMock = $this->createMock(QueryInterface::class);
        $searchFilter = new SearchDateFilter('date');
        $searchFilter->setValue(['gte' => '2020-03-03']);
        $date = $searchFilter->getValue();
        $this->assertInstanceOf(\DateTime::class, $date);
        $this->assertInstanceOf(
            QueryExpression::class,
            $searchFilter->process(new QueryExpression(), 'test', $queryMock)
        );

        /** Test first date */
        /** @var \DateTime $date */
        $this->assertEquals('2020', $date->format('Y'), 'Year is 2020.');
        $this->assertEquals('Mar', $date->format('M'), 'Month is marc');
        $this->assertEquals('03', $date->format('d'), 'Day is 03');
    }
}
