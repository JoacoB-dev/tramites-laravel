<?php

namespace App\Providers;

use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // En desarrollo y tests: avisar de N+1 y de atributos inexistentes
        Model::shouldBeStrict(! $this->app->isProduction());

        Paginator::useBootstrapFive();
        Carbon::setLocale(config('app.locale'));
        CarbonImmutable::setLocale(config('app.locale'));

        // @pesos($importe) => "$ 18.500,00"
        Blade::directive('pesos', fn (string $expresion) => "<?php echo e(\\Illuminate\\Support\\Number::currency((float) ({$expresion}), 'ARS', 'es_AR')); ?>");

        // Gates por rol (las reglas por modelo están en app/Policies)
        Gate::define('acceder-panel', fn (User $user) => $user->esStaff());
        Gate::define('acceder-portal', fn (User $user) => $user->esProfesional() && $user->profesional()->exists());

        // Login de la API: 5 intentos por minuto por email + IP
        RateLimiter::for('api-login', function (Request $request) {
            return Limit::perMinute(5)->by(mb_strtolower((string) $request->input('email')).'|'.$request->ip());
        });

        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });
    }
}
