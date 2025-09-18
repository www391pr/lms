<?php
namespace App\Traits;
trait filterCourses
{
    public function filterCourses($request, $coursesQuery = null)
    {
        $key = $request->get('key', '');

        if ($coursesQuery && !empty($key)) {
            $coursesQuery->where(function ($query) use ($key) {
                $query->where('title', 'LIKE', '%' . $key . '%')
                    ->orWhere('description', 'LIKE', '%' . $key . '%')
                    ->orWhereHas('instructor', function ($instructorQuery) use ($key) {
                        $instructorQuery->where('full_name', 'LIKE', '%' . $key . '%');
                    });
            });
        }

        if ($instructor = $request->get('instructor')) {
            if ($coursesQuery) {
                $coursesQuery->whereHas('instructor', function ($query) use ($instructor) {
                    $query->where('full_name', 'LIKE', '%' . $instructor . '%');
                });
            }
        }

        if ($minPrice = $request->get('min_price')) {
            if ($coursesQuery) {
                $coursesQuery->where('price', '>=', $minPrice);
            }
        }

        if ($maxPrice = $request->get('max_price')) {
            if ($coursesQuery) {
                $coursesQuery->where('price', '<=', $maxPrice);
            }
        }

        if ($categoryNames = $request->get('category')) {
            if ($coursesQuery) {
                $coursesQuery->whereHas('categories', function ($query) use ($categoryNames) {
                    $query->whereIn('name', $categoryNames);
                });
            }
        }

        if ($date = $request->get('date')) {
            if ($coursesQuery) {
                $coursesQuery->where('created_at', '>=', $date);
            }
        }

        if ($minViews = $request->get('min_views')) {
            if ($coursesQuery) {
                $coursesQuery->where('views', '>=', $minViews);
            }
        }
        if ($minRating = $request->get('min_rating')) {
            if ($coursesQuery) {
                $coursesQuery->where('rating', '>=', $minRating);
            }
        }
        if ($status = $request->get('enabled_status')) {
            if ($coursesQuery && in_array($status, ['enabled', 'disabled', 'all'])) {
                if ($status === 'enabled') {
                    $coursesQuery->where('enabled', true);
                } elseif ($status === 'disabled') {
                    $coursesQuery->where('enabled', false);
                }
            }
        }
        return $coursesQuery;
    }
}
