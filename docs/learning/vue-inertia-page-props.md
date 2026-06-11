# Vue Inertia Page Props

## What it is

An Inertia page is a Vue component that Laravel renders as a full page without returning a Blade view for that page.

In AutoService, the controller chooses the page component name and sends data to it as props:

```php
return Inertia::render('Dashboard', [
    'activeWorkshop' => [
        'id' => $activeWorkshop->id,
        'name' => $activeWorkshop->name,
        'slug' => $activeWorkshop->slug,
    ],
    'bookingRequests' => $bookingRequestsQuery->handle($activeWorkshop),
]);
```

Inertia then loads `resources/js/pages/Dashboard.vue` and gives that Vue component the `activeWorkshop` and `bookingRequests` props.

The useful mental model:

```txt
Laravel controller -> Inertia::render(page name, props) -> Vue page defineProps()
```

## Where we use it in AutoService

Current examples:

- `app/Http/Controllers/DashboardController.php`
- `resources/js/pages/Dashboard.vue`
- `app/Http/Controllers/PublicBookingRequestController.php`
- `resources/js/pages/PublicBookingRequests/Create.vue`
- `resources/js/pages/PublicBookingRequests/Success.vue`

`DashboardController` renders the authenticated dashboard page:

```php
return Inertia::render('Dashboard', [
    'activeWorkshop' => [
        'id' => $activeWorkshop->id,
        'name' => $activeWorkshop->name,
        'slug' => $activeWorkshop->slug,
    ],
    'bookingRequests' => $bookingRequestsQuery->handle($activeWorkshop),
]);
```

`PublicBookingRequestController` renders the public booking request form:

```php
return Inertia::render('PublicBookingRequests/Create', [
    'workshop' => [
        'name' => $workshop->name,
        'slug' => $workshop->slug,
    ],
]);
```

It also renders the success page with the same `workshop` prop:

```php
return Inertia::render('PublicBookingRequests/Success', [
    'workshop' => [
        'name' => $workshop->name,
        'slug' => $workshop->slug,
    ],
]);
```

## How Laravel passes props to Vue

The first argument to `Inertia::render()` is the Vue page name.

The second argument is an array of props. Those keys become props in the Vue page.

For `Inertia::render('Dashboard', [...])`, Inertia maps the page name to:

```txt
resources/js/pages/Dashboard.vue
```

For `Inertia::render('PublicBookingRequests/Create', [...])`, Inertia maps the page name to:

```txt
resources/js/pages/PublicBookingRequests/Create.vue
```

The prop names must match between Laravel and Vue.

Laravel sends:

```php
'workshop' => [
    'name' => $workshop->name,
    'slug' => $workshop->slug,
],
```

Vue receives:

```ts
const props = defineProps<{
    workshop: {
        name: string;
        slug: string;
    };
}>();
```

## What defineProps does

`defineProps` is a Vue `<script setup>` helper. It declares which props the page expects from its parent.

In an Inertia page, the "parent" is effectively the Inertia response from Laravel.

In `Dashboard.vue`, the page declares two props:

```ts
defineProps<{
    activeWorkshop: {
        id: number;
        name: string;
        slug: string;
    };
    bookingRequests: {
        id: number;
        customerName: string;
        customerPhone: string;
        problemDescription: string;
        preferredDate: string | null;
        status: {
            value: 'new' | 'confirmed' | 'rejected' | 'cancelled';
            label: string;
        };
        vehicle: {
            brand: string | null;
            model: string | null;
            licensePlate: string | null;
        } | null;
        createdAt: string;
    }[];
}>();
```

This TypeScript shape documents what the template can safely use. It does not fetch the data. Laravel already sent the data through Inertia.

In `PublicBookingRequests/Create.vue`, the page stores the props in a `props` variable because the script needs `props.workshop.slug` when submitting the form:

```ts
const props = defineProps<{
    workshop: {
        name: string;
        slug: string;
    };
}>();
```

The template can still use `workshop.name` directly:

```vue
<Head :title="`Book ${workshop.name}`" />
```

The script uses `props.workshop.slug`:

```ts
form.post(route('public-booking-requests.store', props.workshop.slug), {
    preserveScroll: true,
});
```

## Dashboard example

`DashboardController` resolves the active workshop through the user's workshop memberships, stores the active workshop id in session when needed, then renders the dashboard.

The controller sends:

- `activeWorkshop`: the workshop id, name, and slug
- `bookingRequests`: booking request rows for the active workshop

`Dashboard.vue` renders the active workshop name:

```vue
<h1 class="text-xl font-semibold text-foreground">{{ activeWorkshop.name }}</h1>
```

It renders an empty state when there are no booking requests:

```vue
<div v-if="bookingRequests.length === 0">
    No booking requests yet.
</div>
```

It renders the table when there are booking requests:

```vue
<tr v-for="bookingRequest in bookingRequests" :key="bookingRequest.id">
    <td>{{ bookingRequest.customerName }}</td>
    <td>{{ bookingRequest.problemDescription }}</td>
    <td>{{ bookingRequest.status.label }}</td>
</tr>
```

The page also uses small helper functions like `formatDate`, `formatDateTime`, and `vehicleSummary` to keep formatting logic out of the HTML table markup.

## Public booking request example

`PublicBookingRequestController::create()` receives a `Workshop` route model and sends only the fields the page needs:

```php
'workshop' => [
    'name' => $workshop->name,
    'slug' => $workshop->slug,
],
```

`Create.vue` displays the workshop name:

```vue
<p class="text-sm font-medium text-muted-foreground">{{ workshop.name }}</p>
```

It submits the form to the public booking request store route:

```ts
const submit = () => {
    form.post(route('public-booking-requests.store', props.workshop.slug), {
        preserveScroll: true,
    });
};
```

`Success.vue` receives the same `workshop` prop and uses the slug to link back to the create page:

```vue
<Link :href="route('public-booking-requests.create', props.workshop.slug)">
    Send another request
</Link>
```

## Basic template rendering

### Text interpolation

Use `{{ }}` when rendering text.

AutoService example:

```vue
{{ activeWorkshop.name }}
```

Another example:

```vue
{{ bookingRequest.customerPhone }}
```

Do not use `{{ }}` inside Vue attributes. Use `:` binding for attributes.

Good:

```vue
<Head :title="`Book ${workshop.name}`" />
```

### Conditional rendering

Use `v-if` when something should only exist in the page under a condition.

AutoService example:

```vue
<div v-if="bookingRequests.length === 0">
    No booking requests yet.
</div>
```

`Dashboard.vue` pairs this with `v-else` for the table:

```vue
<div v-else class="overflow-x-auto">
    ...
</div>
```

### List rendering

Use `v-for` to repeat markup for each item in an array.

AutoService example:

```vue
<tr v-for="bookingRequest in bookingRequests" :key="bookingRequest.id">
    <td>{{ bookingRequest.customerName }}</td>
</tr>
```

Always provide a stable `:key`. Here, `bookingRequest.id` is stable because it comes from the database row.

### Event submit

Use `@submit.prevent` on a form to run a Vue function and prevent the browser's default full page form submission.

AutoService example:

```vue
<form class="space-y-8" @submit.prevent="submit">
```

That calls:

```ts
const submit = () => {
    form.post(route('public-booking-requests.store', props.workshop.slug), {
        preserveScroll: true,
    });
};
```

## useForm briefly

`useForm` is an Inertia helper for form state.

In `Create.vue`, it stores the current input values:

```ts
const form = useForm({
    customer_name: '',
    customer_phone: '',
    problem_description: '',
    preferred_date: '',
    vehicle: {
        brand: '',
        model: '',
        license_plate: '',
    },
});
```

The template binds inputs to that state with `v-model`:

```vue
<Input id="customer_name" v-model="form.customer_name" type="text" name="customer_name" />
```

When the form is posted, Inertia sends the form data to Laravel:

```ts
form.post(route('public-booking-requests.store', props.workshop.slug), {
    preserveScroll: true,
});
```

The same `form` object also exposes validation errors and processing state:

```vue
<InputError :message="form.errors.customer_name" />
```

```vue
<Button type="submit" :disabled="form.processing">
```

That is why `Create.vue` does not manually create a separate `errors` object or loading flag.

## Common mistakes

- Sending a prop from Laravel with one name and expecting a different name in Vue. If Laravel sends `activeWorkshop`, Vue should declare `activeWorkshop`, not `workshop`.
- Declaring the wrong TypeScript shape in `defineProps`. If Laravel sends `preferredDate` as `string | null`, the Vue type should allow `null`.
- Forgetting that `defineProps` declares received data; it does not load data by itself.
- Using `{{ }}` inside attributes instead of `:` bindings.
- Using `v-for` without a stable `:key`.
- Mutating props directly instead of putting editable form values in `useForm`.
- Forgetting `.prevent` on form submit and accidentally allowing a full browser form submission.
- Expecting the public booking request form fields to match the Vue camelCase dashboard fields. The public form posts snake_case names like `customer_name`; the dashboard displays camelCase props like `customerName`.

## Mini exercise

Open these files side by side:

- `app/Http/Controllers/PublicBookingRequestController.php`
- `resources/js/pages/PublicBookingRequests/Create.vue`

Trace the `workshop` prop:

1. Find where Laravel creates the `workshop` array.
2. Find where Vue declares the `workshop` prop with `defineProps`.
3. Find where the template renders `workshop.name`.
4. Find where the submit function uses `props.workshop.slug`.
5. Write one sentence explaining why the page needs `name` for display and `slug` for route generation.

## Self-check questions

1. What does the first argument to `Inertia::render()` choose?
2. Where does `Dashboard.vue` get `activeWorkshop.name` from?
3. Why does `Create.vue` assign `defineProps` to `const props`?
4. When should you use `{{ }}` and when should you use `:` binding?
5. What does `@submit.prevent="submit"` prevent?
6. Why does `v-for` need `:key="bookingRequest.id"`?
7. What does `useForm` give this project besides storing input values?
