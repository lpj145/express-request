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
}
