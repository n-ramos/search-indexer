<?php

namespace Nramos\SearchIndexer\Tests;

use Nramos\SearchIndexer\Meilisearch\Filter\MeiliSearchFilter;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Small] final class SearchFilterTest extends TestCase
{
    public function testBasicFilter()
    {
        $filter = (new MeiliSearchFilter())->addFilter('status', '=', 'active');
        self::assertSame('status = "active"', $filter->toString());
    }

    public function testInFilter()
    {
        $filter = (new MeiliSearchFilter())->addInFilter('role', ['admin', 'user']);
        self::assertSame('role IN ["admin", "user"]', $filter->toString());
    }

    public function testLocationFilter()
    {
        $filter = (new MeiliSearchFilter())->addLocationFilter('radius', 48.8566, 2.3522, 5, 'km');
        self::assertSame('_geoRadius(48.856600, 2.352200, 5km)', $filter->toString());
    }

    public function testExistenceFilter()
    {
        $filter = (new MeiliSearchFilter())->addExistenceFilter('release_date', true);
        self::assertSame('release_date EXISTS', $filter->toString());

        $filter = (new MeiliSearchFilter())->addExistenceFilter('overview', false);
        self::assertSame('overview NOT EXISTS', $filter->toString());
    }

    public function testComplexFilter()
    {
        $filter = (new MeiliSearchFilter())
            ->addFilter('status', '=', 'active')
            ->addFilter('rating.users', '>', 85)
            ->openParenthesis()
            ->addFilter('genres', '=', 'horror', 'OR')
            ->addFilter('genres', '=', 'comedy')
            ->closeParenthesis()
            ->openParenthesis()
            ->addFilter('genres', '=', 'horror')
            ->addFilter('genres', '=', 'comedy')
            ->closeParenthesis()
            ->addInFilter('role', ['admin', 'user'])
            ->addLocationFilter('radius', 48.8566, 2.3522, 5, 'km')
            ->addLocationBounding('bounding', [48.8566, 2.3522, 49.8566, 2.4522], 'km')
            ->addExistenceFilter('release_date')
            ->addExistenceFilter('overview', false)
        ;

        $expected = 'status = "active" AND rating.users > "85" AND (genres = "horror" OR genres = "comedy") AND (genres = "horror" AND genres = "comedy") AND role IN ["admin", "user"] AND _geoRadius(48.856600, 2.352200, 5km) AND _geoBoundingBox([48.856600, 2.352200], [49.856600, 2.452200]) AND release_date EXISTS AND overview NOT EXISTS';
        self::assertSame($expected, $filter->toString());
    }
}
