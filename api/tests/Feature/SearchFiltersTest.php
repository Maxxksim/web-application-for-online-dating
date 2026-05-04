<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use Tests\TestCase;

class SearchFiltersTest extends TestCase
{
    private array $genderFilters = ['man', 'woman', 'both'];

    public function test_search_filters_by_gender(): void
    {
        $userData = $this->getUser();

        $token = $userData['token'];
        $user = $userData['user'];

        $userGender = 'man';

        $user->profile->update(['gender' => $userGender]);

        foreach ($this->genderFilters as $genderFilter) {

            $this->withToken($token)->patch('api/search/filters', ['gender' => $genderFilter]);
            $response = $this->withToken($token)->get('/api/search/profiles');
            $profiles = $response->json('profiles.data');

            foreach ($profiles as $profile) {

                $foundUser = User::find($profile['user_id']);

                if ($genderFilter === 'both') {
                    $this->assertContains($profile['gender'], ['man', 'woman']);
                } else {
                    $this->assertEquals($genderFilter, $profile['gender']);
                }

                $this->assertContains($foundUser->searchFilter->gender, ['both', $userGender]);
            }
        }
    }
}
