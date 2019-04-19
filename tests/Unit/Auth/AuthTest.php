<?php declare (strict_types = 1);

namespace Tests\Unit\Auth;

use Ramsey\Uuid\Uuid;
use Tests\BaseTestCase;
use Tests\DatabaseTrait;
use Wallet\System\Infrastructure\JWT;
use Wallet\System\Infrastructure\StatusMessage;

class AuthTest extends BaseTestCase
{
    use DatabaseTrait;

    /** @test */
    public function that_login_user_works()
    {
        $id = Uuid::uuid4();

        $this->createUser($id, 'example@test.com', 'secret');

        $response = $this->runApp('POST', '/api/auth/login', [
            'email'    => 'example@test.com',
            'password' => 'secret',
        ]);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($response->isOk());

        $body = json_decode((string) $response->getBody(), true);

        $this->assertArrayHasKey('token', $body['data']);

        $tokenData = JWT::decode($body['data']['token'], getenv('JWT_KEY'), ['HS256']);

        $this->assertEquals((string) $id, $tokenData->id);
    }

    /** @test */
    public function that_failed_login_user_works()
    {
        $this->createUser(Uuid::uuid4(), 'example@test.com', 'test');

        $response = $this->runApp('POST', '/api/auth/login', [
            'email'    => 'example@test.com',
            'password' => 'fail',
        ]);

        $this->assertEquals(401, $response->getStatusCode());

        $body = json_decode((string) $response->getBody(), true);

        $this->assertEquals(StatusMessage::LOGIN_ERROR, $body['error']);
    }

    /** @test */
    public function that_register_user_works()
    {
        $response = $this->runApp('POST', '/api/auth/register', [
            'email'    => 'example@test.com',
            'password' => 'test',
        ]);

        $this->assertEquals(200, $response->getStatusCode());

        $this->assertDatabaseHas('users', [
            'email' => 'example@test.com',
        ]);

        $body = json_decode((string) $response->getBody(), true);

        $this->assertArrayHasKey('token', $body['data']);

        $tokenData = JWT::decode($body['data']['token'], getenv('JWT_KEY'), ['HS256']);
    }
}
