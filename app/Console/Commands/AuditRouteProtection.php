<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as RouteFacade;

class AuditRouteProtection extends Command
{
    protected $signature = 'security:audit-routes';
    
    protected $description = 'Audit all routes for missing security middleware';
    
    public function handle()
    {
        $this->info('Starting route protection audit...');
        
        $routes = RouteFacade::getRoutes();
        $unprotectedRoutes = [];
        $publicRoutes = [
            'login', 'register', 'password.request', 'password.email', 
            'password.reset', 'password.update', 'verification.notice', 
            'verification.verify', 'verification.send', 'google.signin',
            'auth/google/callback', 'index'
        ];
        
        foreach ($routes as $route) {
            $name = $route->getName();
            $uri = $route->uri();
            $middleware = $route->middleware();
            
            // Skip API documentation and other safe routes
            if (str_starts_with($uri, 'api/documentation') || 
                str_starts_with($uri, '_ignition') ||
                str_starts_with($uri, 'telescope') ||
                in_array($name, $publicRoutes)) {
                continue;
            }
            
            // Check if route has authentication middleware
            $hasAuth = $this->hasAuthMiddleware($middleware);
            $hasRole = $this->hasRoleMiddleware($middleware);
            
            if (!$hasAuth && !in_array($name, $publicRoutes)) {
                $unprotectedRoutes[] = [
                    'name' => $name ?? 'unnamed',
                    'uri' => $uri,
                    'methods' => implode('|', $route->methods()),
                    'middleware' => implode(', ', $middleware),
                    'issue' => 'Missing authentication'
                ];
            } elseif ($hasAuth && $this->needsRoleProtection($route) && !$hasRole) {
                $unprotectedRoutes[] = [
                    'name' => $name ?? 'unnamed',
                    'uri' => $uri,
                    'methods' => implode('|', $route->methods()),
                    'middleware' => implode(', ', $middleware),
                    'issue' => 'Missing role-based protection'
                ];
            }
        }
        
        if (empty($unprotectedRoutes)) {
            $this->info('✅ All routes are properly protected!');
            return Command::SUCCESS;
        }
        
        $this->error('⚠️  Found ' . count($unprotectedRoutes) . ' routes with security issues:');
        $this->table(
            ['Route Name', 'URI', 'Methods', 'Middleware', 'Issue'],
            $unprotectedRoutes
        );
        
        return Command::FAILURE;
    }
    
    private function hasAuthMiddleware(array $middleware): bool
    {
        $authMiddleware = ['auth', 'auth:sanctum', 'auth:api'];
        return !empty(array_intersect($middleware, $authMiddleware));
    }
    
    private function hasRoleMiddleware(array $middleware): bool
    {
        foreach ($middleware as $m) {
            if (str_starts_with($m, 'role:') || 
                $m === 'admin_or_owner' || 
                $m === 'daily_report_access') {
                return true;
            }
        }
        return false;
    }
    
    private function needsRoleProtection(Route $route): bool
    {
        $sensitivePatterns = [
            '/users', '/owners', '/managers', '/stores', '/daily-reports',
            '/transaction-types', '/revenue-income-types', '/audit-logs'
        ];
        
        foreach ($sensitivePatterns as $pattern) {
            if (str_contains($route->uri(), $pattern)) {
                return true;
            }
        }
        
        return false;
    }
}