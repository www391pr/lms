<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Course;
use App\Models\Instructor;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use GuzzleHttp\Client;
use Illuminate\Support\Str;

class CoursesTableSeeder extends Seeder
{
    protected $client;
    protected $unsplashAccessKey;

    public function __construct()
    {
        $this->client = new Client(['timeout' => 15]);
        // Get your Unsplash Access Key from https://unsplash.com/developers
        $this->unsplashAccessKey = '8A8yBeu95vlani8wv2trcyhqI4VSYe-atH-rmLVPj_g';
    }

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $coursesData = [
            'Introduction to Programming' => 'Learn the basics of programming, including variables, control structures, and data types.',
            'Advanced Web Development' => 'Dive deeper into web development with frameworks, APIs, and responsive design.',
            'Data Science Essentials' => 'Explore the fundamentals of data analysis, visualization, and machine learning.',
            'Machine Learning Basics' => 'Understand the principles of machine learning and how to apply them to real-world problems.',
            'UI/UX Design Principles' => 'Discover the core principles of user interface and user experience design.',
            'Digital Marketing Strategies' => 'Learn effective strategies for online marketing, SEO, and social media.',
            'Cybersecurity Fundamentals' => 'Gain insights into cybersecurity threats and how to protect information systems.',
            'Mobile App Development' => 'Create mobile applications for iOS and Android using popular frameworks.',
            'Cloud Computing Concepts' => 'Understand cloud computing models, services, and deployment strategies.',
            'Game Development with Unity' => 'Develop engaging games using Unity, focusing on graphics, physics, and gameplay.',
            'Blockchain Technology' => 'Explore the principles of blockchain and its applications in various industries.',
            'Photography Basics' => 'Learn the fundamentals of photography, including composition, lighting, and editing.',
            'Public Speaking Skills' => 'Enhance your public speaking abilities and learn effective communication techniques.',
            'Financial Literacy for Beginners' => 'Understand personal finance, budgeting, and investment strategies.',
            'Creative Writing Workshop' => 'Develop your writing skills through exercises and feedback on your work.',
        ];

        // Map course titles to Unsplash search queries
        $unsplashQueries = [
            'Introduction to Programming' => 'programming code',
            'Advanced Web Development' => 'web development',
            'Data Science Essentials' => 'data science analytics',
            'Machine Learning Basics' => 'machine learning AI',
            'UI/UX Design Principles' => 'UI UX design',
            'Digital Marketing Strategies' => 'digital marketing',
            'Cybersecurity Fundamentals' => 'cybersecurity',
            'Mobile App Development' => 'mobile app development',
            'Cloud Computing Concepts' => 'cloud computing',
            'Game Development with Unity' => 'game development',
            'Blockchain Technology' => 'blockchain technology',
            'Photography Basics' => 'photography',
            'Public Speaking Skills' => 'public speaking',
            'Financial Literacy for Beginners' => 'financial literacy',
            'Creative Writing Workshop' => 'creative writing',
        ];

        $instructors = Instructor::all();
        $categories = Category::all();

        foreach ($instructors as $instructor) {
            $numberOfCourses = rand(1, 3);

            // Get random course titles for this instructor
            $randomTitles = array_rand($coursesData, $numberOfCourses);
            if (!is_array($randomTitles)) {
                $randomTitles = [$randomTitles];
            }

            foreach ($randomTitles as $title) {
                // Get or download image for the course from Unsplash
                $imagePath = $this->getCourseImageFromUnsplash($title, $unsplashQueries[$title]);

                $course = Course::create([
                    'instructor_id' => $instructor->id,
                    'title' => $title,
                    'image' => $imagePath,
                    'views' => rand(0, 100),
                    'description' => $coursesData[$title],
                    'price' => rand(1, 20) . '0',
                    'level' => rand(1, 5) > 2 ? rand(1, 5) : null,
                    'discount' => rand(0, 10) > 5 ? rand(1, 30) : 0,
                    'rating' => 0,
                ]);

                // Handle categories
                $randomCategories = $categories->random(rand(1, min(2, $categories->count())));
                $attachIds = [];
                foreach ($randomCategories as $category) {
                    $attachIds[] = $category->id;
                    if ($category->parent_id) {
                        $attachIds[] = $category->parent_id;
                    }
                }
                $course->categories()->attach(array_unique($attachIds));
                $instructor->categories()->syncWithoutDetaching(array_unique($attachIds));
            }
        }
    }

    /**
     * Get or download image for course title from Unsplash
     */
    protected function getCourseImageFromUnsplash(string $title, string $query): string
    {
        $slug = Str::slug($title);

        try {
            // Get image URL from Unsplash
            $imageUrl = $this->getUnsplashImageUrl($query);

            // Download the image
            $response = $this->client->get($imageUrl);
            $imageContent = $response->getBody()->getContents();

            // Store the image in the custom path format
            return $this->storeImageWithCustomPath($imageContent, $slug);

        } catch (\Exception $e) {
            // Fallback to a placeholder service if Unsplash fails
            return $this->getPlaceholderImage($title);
        }
    }

    /**
     * Store image with custom path format
     */
    protected function storeImageWithCustomPath(string $imageContent, string $slug): string
    {
        // Define the filename and path
        $filename = $slug . '.jpg';
        $storagePath = 'images/course-images/' . $filename;

        // Store the image using the Storage facade
        Storage::disk('public')->put($storagePath, $imageContent);

        // Return the path in the format: 'storage/images/course-images/filename.jpg'
        return 'storage/' . $storagePath;
    }

    /**
     * Get image URL from Unsplash API
     */
    protected function getUnsplashImageUrl(string $query): string
    {
        // If no Unsplash access key is provided, use a fallback
        if (empty($this->unsplashAccessKey)) {
            return $this->getPlaceholderImageUrl($query);
        }

        try {
            // Make request to Unsplash API
            $response = $this->client->get('https://api.unsplash.com/photos/random', [
                'headers' => [
                    'Authorization' => 'Client-ID ' . $this->unsplashAccessKey,
                    'Accept-Version' => 'v1',
                ],
                'query' => [
                    'query' => $query,
                    'orientation' => 'landscape',
                    'fit' => 'crop',
                    'w' => 600,
                    'h' => 400,
                ]
            ]);

            $data = json_decode($response->getBody(), true);

            // Return the regular size image URL
            return $data['urls']['regular'] ?? $this->getPlaceholderImageUrl($query);

        } catch (\Exception $e) {
            return $this->getPlaceholderImageUrl($query);
        }
    }

    /**
     * Fallback placeholder image service
     */
    protected function getPlaceholderImageUrl(string $query): string
    {
        // Using a placeholder service as fallback
        $encodedQuery = urlencode($query);
        return "https://source.unsplash.com/featured/600x400/?{$encodedQuery}";
    }

    /**
     * Get a placeholder image if all else fails
     */
    protected function getPlaceholderImage(string $title): string
    {
        $slug = Str::slug($title);

        try {
            // Create a simple colored placeholder with text
            $colors = [
                'Programming' => '2563eb', 'Web Development' => '4f46e5',
                'Data Science' => '7c3aed', 'Machine Learning' => '9333ea',
                'UI/UX Design' => 'c026d3', 'Digital Marketing' => 'db2777',
                'Cybersecurity' => 'dc2626', 'Mobile App' => 'ea580c',
                'Cloud Computing' => '65a30d', 'Game Development' => '0891b2',
                'Blockchain' => '0891b2', 'Photography' => '8b5cf6',
                'Public Speaking' => '6366f1', 'Financial Literacy' => '0d9488',
                'Creative Writing' => 'ca8a04'
            ];

            $color = '2563eb'; // Default blue
            foreach ($colors as $key => $value) {
                if (stripos($title, $key) !== false) {
                    $color = $value;
                    break;
                }
            }

            $placeholderUrl = "https://placehold.co/600x400/{$color}/white?font=montserrat&text=" . urlencode($title);

            // Download the placeholder image
            $response = $this->client->get($placeholderUrl);
            $imageContent = $response->getBody()->getContents();

            // Store using the custom path format
            return $this->storeImageWithCustomPath($imageContent, $slug);
        } catch (\Exception $e) {
            // Ultimate fallback - check if default image exists
            $defaultPath = 'images/course-images/default.png';
            if (!Storage::disk('public')->exists($defaultPath)) {
                // Create a simple default image
                $defaultSvg = '<svg xmlns="http://www.w3.org/2000/svg" width="600" height="400" viewBox="0 0 600 400"><rect width="100%" height="100%" fill="#cccccc"/><text x="300" y="200" font-family="Arial" font-size="24" fill="#666666" text-anchor="middle">Course Image</text></svg>';
                Storage::disk('public')->put($defaultPath, $defaultSvg);
            }
            return 'storage/' . $defaultPath;
        }
    }
}
