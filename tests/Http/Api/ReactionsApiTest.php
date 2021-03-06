<?php

namespace Tests\Http\Api;

use Rogue\Models\Post;
use Rogue\Models\Signup;
use Rogue\Models\Reaction;
use Tests\BrowserKitTestCase;

class ReactionsApiTest extends BrowserKitTestCase
{
    /**
     * Test that the POST /reactions request creates a reaction for a post.
     * Also test that when POST /reactions is hit again by the same user
     * for the same post, the reaction is soft deleted.
     *
     * POST /reactions
     * @return void
     */
    public function testPostingAndSoftDeleteForReaction()
    {
        // Create a post to react to.
        $post = factory(Post::class)->create();

        $northstarId = $this->faker->uuid;

        // Create a reaction.
        $this->withRogueApiKey()->post('api/v2/reactions', [
            'northstar_id' => $northstarId,
            'post_id' => $post->id,
        ]);

        $this->assertResponseStatus(200);

        // Make sure this creates a reaction.
        $this->seeJsonSubset([
            'meta' => [
                'total_reactions' => 1,
            ],
        ]);

        // React (unlike) again to the same post with the same user.
        $this->withRogueApiKey()->post('api/v2/reactions', [
            'northstar_id' => $northstarId,
            'post_id' => $post->id,
        ]);

        // This should now be a 201 code because it was updated.
        // @TODO update this when we do an audit of status codes in Rogue.
        $this->assertResponseStatus(201);

        // Make sure this reaction is soft deleted.
        $this->seeJsonSubset([
            'meta' => [
                'total_reactions' => 0,
            ],
        ]);
    }

    /**
     * Test that the aggregate of total reactions for a post is correct.
     *
     * POST /reactions
     * @return void
     */
    public function testAggregateReactions()
    {
        $post = factory(Post::class)->create();

        // Create a reaction.
        $this->withRogueApiKey()->post('api/v2/reactions', [
            'northstar_id' => $this->faker->uuid,
            'post_id' => $post->id,
        ]);

        // Make sure this creates a reaction.
        $this->assertResponseStatus(200);
        $this->seeJsonSubset([
            'meta' => [
                'total_reactions' => 1,
            ],
        ]);

        // A second user reacts to the same post..
        $this->withRogueApiKey()->post('api/v2/reactions', [
            'northstar_id' => $this->faker->uuid,
            'post_id' => $post->id,
        ]);

        // Make sure this creates a reaction and increases total_reaction count.
        $this->assertResponseStatus(200);
        $this->seeJsonSubset([
            'meta' => [
                'total_reactions' => 2,
            ],
        ]);
    }

    /**
     * Test that a post and signup's updated_at updates when a reaction is made.
     *
     * @return void
     */
    public function testUpdatedPostAndSignupWithReaction()
    {
        $this->mockTime('8/3/2017 14:00:00');

        // Create a signup and a post, and associate them to each other.
        $signup = factory(Signup::class)->create();
        $post = factory(Post::class)->create();
        $post->signup()->associate($signup);

        // And then later on, someone likes the post.
        $this->mockTime('8/3/2017 17:30:00');
        $reaction = factory(Reaction::class)->create([
            'post_id' => $post->id,
        ]);

        // Make sure the signup and post's updated_at matches the reaction created_at time.
        $this->assertEquals($reaction->created_at, $post->fresh()->updated_at);

        // @TODO: Laravel doesn't touch timestamps recursively - only direct relationships.
        // $this->assertEquals($reaction->created_at, $signup->fresh()->updated_at);
        $this->markTestIncomplete();
    }
}
