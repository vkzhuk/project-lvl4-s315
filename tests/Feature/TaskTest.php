<?php

namespace Tests\Feature;

use App\User;
use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TaskTest extends TestCase
{
    use RefreshDatabase;
    protected $user;
    protected $taskStatus;

    public function setUp()
    {
        parent::setUp();
        $this->user = factory(User::class)->create(['name' => 'testName']);
        $
    }

    public function testGetTasksList()
    {
        $response = $this->actingAs($this->user)->get(route('tasks.index'));

        $response->assertOk();
    }

    public function testGetTaskCreatePage()
    {
        $response = $this->actingAs($this->user)->get(route('tasks.create'));

        $response->assertOk();
    }

    public function testStoreTask()
    {
        $response = $this->actingAs($this->user)->post(
            route(
                'tasks.store',
                ['name' => 'testTask', 'description' => 'descriptionTest']
            )
        );

        $this->assertDatabaseHas(
            'tasks',
            [
                'name' => 'testTask',
                'description' => 'descriptionTest',
                'creator_id' => $this->user->id
            ]
        );
    }
}