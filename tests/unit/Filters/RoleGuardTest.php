<?php

namespace Tests\Unit\Filters;

use App\Filters\RoleGuard;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Services;

final class RoleGuardTest extends CIUnitTestCase
{
    protected RoleGuard $filter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->filter = new RoleGuard();
    }

    public function testBeforeWithoutSessionRedirectsToLogin(): void
    {
        $session = Services::session();
        $session->destroy();

        $request = Services::request();
        $result = $this->filter->before($request, ['master', 'admin']);

        $this->assertNotNull($result);
        $this->assertInstanceOf(\CodeIgniter\HTTP\RedirectResponse::class, $result);
        $this->assertSame(302, $result->getStatusCode());
    }

    public function testBeforeWithMasterRoleBypassesRoleCheck(): void
    {
        $session = Services::session();
        $session->set(['user_id' => 1, 'user_role' => 'Master']);

        $request = Services::request();
        $result = $this->filter->before($request, ['admin', 'editor']);

        $this->assertNull($result);
    }

    public function testBeforeWithAllowedRolePasses(): void
    {
        $session = Services::session();
        $session->set(['user_id' => 2, 'user_role' => 'Admin']);

        $request = Services::request();
        $result = $this->filter->before($request, ['master', 'admin']);

        $this->assertNull($result);
    }

    public function testBeforeWithUnauthorizedRoleRedirectsToDashboard(): void
    {
        $session = Services::session();
        $session->set(['user_id' => 3, 'user_role' => 'Author']);

        $request = Services::request();
        $result = $this->filter->before($request, ['master', 'admin']);

        $this->assertNotNull($result);
        $this->assertInstanceOf(\CodeIgniter\HTTP\RedirectResponse::class, $result);
        $this->assertSame(302, $result->getStatusCode());
    }

    public function testAfterDoesNothing(): void
    {
        $request = Services::request();
        $response = Services::response();

        $this->assertNull($this->filter->after($request, $response));
    }
}
