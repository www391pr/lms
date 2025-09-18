<?php

namespace App\Traits;

trait SortCourses
{
    public function sortCourses($sortBy, $coursesQuery)
    {
        if ($coursesQuery) {
            switch ($sortBy) {
                case 'price_asc':
                    $coursesQuery->orderBy('price', 'asc');
                    break;
                case 'price_desc':
                    $coursesQuery->orderBy('price', 'desc');
                    break;
                case 'title':
                    $coursesQuery->orderBy('title', 'asc');
                    break;
                case 'date':
                    $coursesQuery->orderBy('created_at', 'desc');
                    break;
                case 'level':
                    $coursesQuery->orderByRaw('COALESCE(level, 1) asc');
                    break;
                case 'views_desc':
                    $coursesQuery->orderBy('views', 'desc');
                    break;
                case 'rating':
                    $coursesQuery->orderBy('rating', 'desc');
                    break;    
            }
        }
        return $coursesQuery;
    }
} 