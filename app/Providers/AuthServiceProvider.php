use Illuminate\Support\Facades\Gate;
use App\Enums\UserRole;

public function boot()
{
    $this->registerPolicies();

    Gate::define('admin-only', function ($user) {
        return $user->role === UserRole::ADMIN;
    });

    Gate::define('owner-only', function ($user) {
        return $user->role === UserRole::OWNER;
    });

    Gate::define('manager-only', function ($user) {
        return $user->role === UserRole::MANAGER;
    });
}