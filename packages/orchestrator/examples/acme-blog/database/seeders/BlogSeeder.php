<?php

namespace Acme\Blog\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BlogSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('blog_posts')->insert([
            'title' => 'Seeded blog post',
            'content' => 'This record was inserted from the Acme Blog module seeder.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
