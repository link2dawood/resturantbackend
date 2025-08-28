use Illuminate\Support\Facades\Gate;

public function boot()
{
    $this->registerPolicies();

    Gate::define('admin-only', function ($user) {
        return $user->role === 'admin';
    });

    Gate::define('owner-only', function ($user) {
        return $user->role === 'owner';
    });

    Gate::define('manager-only', function ($user) {
        return $user->role === 'manager';
    });
}