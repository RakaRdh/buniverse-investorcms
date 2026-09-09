<?php

namespace Tests\Unit\Filters;

use App\Filters\AuthGuard;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Services;

final class AuthGuardTest extends CIUnitTestCase
{
    protected AuthGuard $filter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->filter = new AuthGuard();
    }

    public function testBeforeWithoutSessionRedirectsToLogin(): void
    {
        $session = Services::session();
        $session->destroy();

        $request = Services::request();
        $result = $this->filter->before($request);

        $this->assertNotNull($result);
        $this->assertInstanceOf(\CodeIgniter\HTTP\RedirectResponse::class, $result);
        $this->assertSame(302, $result->getStatusCode());
    }

    public function testBeforeWithActiveSessionAllowsAccess(): void
    {
        $session = Services::session();
        $session->set(['user_id' => 1, 'user_name' => 'Raka']);

        $request = Services::request();
        $result = $this->filter->before($request);

        $this->assertNull($result);
    }

    public function testAfterDoesNotModifyResponse(): void
    {
        $request = Services::request();
        $response = Services::response();

        $this->assertNull($this->filter->after($request, $response));
    }
}
