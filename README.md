
# Teora

## 🛠️ Sistema de Roles, Permisos y Redirección

Este proyecto implementa un sistema de control de acceso granular basado en **permisos**, usando [Spatie Laravel-Permission](https://spatie.be/docs/laravel-permission/). Los **roles** son dinámicos, pero los **permisos** están predefinidos y centralizados. Toda la lógica de visibilidad, protección de rutas y navegación se basa en permisos, no en roles.

---

## 🔐 Autenticación

- El login se realiza en la ruta `/` y utiliza un controlador que autentica y luego redirige al usuario a su primera ruta permitida.
- El archivo `PermissionRouteMap` contiene un mapa entre **permisos** y **rutas de entrada**. Este mapa es utilizado para redirigir al usuario al primer recurso que tiene autorizado luego de iniciar sesión.

```php
// Ejemplo en LoginController
foreach (PermissionRouteMap::map() as $permission => $routeName) {
    if ($user->can($permission)) {
        return redirect()->route($routeName);
    }
}
```

---

## 🧩 Roles y Permisos

**Roles:**
- Son dinámicos: pueden crearse, editarse y eliminarse desde el panel.
- Para crear o asignar roles, el usuario debe tener el permiso `configuracion`.
- Los roles agrupan permisos. Al crear un rol, se seleccionan mediante checkboxes los permisos que se le asignarán.
- Un usuario solo puede tener un rol a la vez.
- Ejemplos: `admin`, `supervisor`, `supervisor tecnico`, `tecnico`.

**Permisos:**
- Son fijos y están centralizados en el helper `PermissionRouteMap`.
- Cada permiso está asociado a una vista principal mediante el nombre de ruta correspondiente.
- El acceso a rutas se protege con middleware `permission:<permiso>`. Ejemplo:

```php
Route::middleware(['permission:ver servicio tecnico|gestionar servicio tecnico'])->group(function () {
    Route::get('servicio-tecnico', ...);
});
```
Algunos permisos están subdivididos (`ver`, `gestionar`), y otros no.

**Ejemplo de asignación en el helper:**
```php
return [
    'inicio' => 'dashboard',
    'ver ordenes' => 'ordenes-fabricacion.index',
    'gestionar ordenes' => 'ordenes-fabricacion.index',
    'stock' => 'stock',
    'ver servicio tecnico' => 'servicio_tecnico',
    'gestionar servicio tecnico' => 'servicio_tecnico',
    // ...
];
```

---

## 🧭 Sidebar y visibilidad en UI

### En vistas controladas por Laravel
El controlador pasa un objeto `can` a la vista con los permisos actuales del usuario:

```php
'can' => [
    'ver' => auth()->user()->can('ver servicio tecnico'),
    'gestionar' => auth()->user()->can('gestionar servicio tecnico'),
]
```

### En Vue
```vue
<button v-if="can.gestionar">Editar</button>
```

### En componentes que no dependen de un controlador
Se accede a los permisos así:

```js
const permissions = usePage().props.auth.user.roles[0].permissions.map(p => p.name);
```
Y luego:

```vue
<NavItem v-if="permissions.includes('ver ordenes') || permissions.includes('gestionar ordenes')" />
```

---

## 🛡️ Protección de Rutas

Todas las rutas del sistema están protegidas mediante permisos. Ejemplo:

```php
Route::middleware(['permission:stock'])->group(function () {
    Route::get('stock', [StockController::class, 'index'])->name('stock');
});
```
Esto asegura que ningún usuario acceda a una ruta que no le corresponde, incluso si intenta hacerlo manualmente desde la URL.

---

## 🧠 Buenas prácticas

- Si agregás un nuevo permiso, asignalo en el helper `PermissionRouteMap`.
- Las rutas deben tener nombres (`->name(...)`) para poder ser utilizadas en el helper. En el caso de que la ruta sea Route::resource pone de valor en el map la url+metodo. Ejemplo: Route::resource('stock'), en el helper seria stock.index la asignacion como nombre de ruta.
- Mantené sincronizados los permisos del backend con la lógica de visibilidad del frontend.
- El flujo de redirección post-login debe mantenerse solo en un lugar (`LoginController`).

---

## 🧩 Archivos relacionados

- `app/Helpers/PermissionRouteMap.php`: Mapea los permisos a rutas.
- `routes/web.php`: Rutas protegidas por middleware permission.
- Componentes Vue: utilizan `can` o `usePage()` para lógica condicional en la UI.

---

## 📚 Recursos

- [Spatie Laravel Permission Docs](https://spatie.be/docs/laravel-permission/)
- [Laravel Middleware](https://laravel.com/docs/10.x/middleware)
- [Inertia.js Shared Props](https://inertiajs.com/shared-data)

