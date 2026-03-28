<?php

namespace Larahammer\Generator\Generators;

class SecurityMiddlewareGenerator extends BaseGenerator
{
    public function generate(): string
    {
        $this->generateCheckRoleMiddleware();
        $this->generateAdminPanelProtectionMiddleware();
        $this->generateUserModelUpdate();

        return "app/Http/Middleware/{CheckRole,AdminPanelProtection}.php + User Model update";
    }

    private function generateCheckRoleMiddleware(): void
    {
        $stub = <<<'PHP'
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * CheckRole Middleware
 * 
 * Usage in routes:
 *   Route::group(['middleware' => 'role:admin,editor'], function () {
 *       // Only admin and editor can access
 *   });
 */
class CheckRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $userRole = auth()->user()->role?->name;

        if (!in_array($userRole, $roles)) {
            abort(403, 'Unauthorized access');
        }

        return $next($request);
    }
}
PHP;

        $path = app_path('Http/Middleware/CheckRole.php');
        $this->writeFile($path, $stub);
    }

    private function generateAdminPanelProtectionMiddleware(): void
    {
        $stub = <<<'PHP'
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * AdminPanelProtection Middleware
 * 
 * Ensures only admin users can access protected routes
 */
class AdminPanelProtection
{
    /**
     * Protect admin panel routes - requires admin role
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check()) {
            return redirect()->route('login')->with('message', 'Please login to access admin panel');
        }

        $user = auth()->user();

        // Check if user has admin role
        if (!$user->role?->is('admin')) {
            abort(403, 'Only administrators can access this panel');
        }

        return $next($request);
    }
}
PHP;

        $path = app_path('Http/Middleware/AdminPanelProtection.php');
        $this->writeFile($path, $stub);
    }

    private function generateUserModelUpdate(): void
    {
        $stub = <<<'PHP'
<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * User belongs to a role
     */
    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * Check if user has a specific role
     */
    public function hasRole(string $roleName): bool
    {
        return $this->role?->is($roleName) ?? false;
    }

    /**
     * Check if user has any of the given roles
     */
    public function hasAnyRole(array $roleNames): bool
    {
        return $this->role?->isAny($roleNames) ?? false;
    }

    /**
     * Check if user is admin
     */
    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }
}
PHP;

        $path = app_path('Models/User.php');
        $this->writeFile($path, $stub);
    }
}
