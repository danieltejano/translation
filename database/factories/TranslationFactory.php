<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Translation>
 */
class TranslationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $languages = ['en', 'es', 'fr', 'de', 'it', 'pt', 'ja', 'zh', 'ko', 'ar'];
        $platforms = ['web', 'mobile', 'desktop', 'api'];
        
        $keys = [
            'button.submit',
            'button.cancel',
            'button.save',
            'button.delete',
            'button.edit',
            'label.email',
            'label.password',
            'label.username',
            'label.phone',
            'message.success',
            'message.error',
            'message.warning',
            'message.info',
            'nav.home',
            'nav.about',
            'nav.contact',
            'nav.profile',
            'nav.settings',
            'form.required',
            'form.optional',
            'validation.required',
            'validation.email',
            'validation.min',
            'validation.max',
            'page.title.home',
            'page.title.dashboard',
            'page.title.profile',
            'error.404',
            'error.500',
            'error.403',
        ];

        $key = fake()->randomElement($keys);
        $lang = fake()->randomElement($languages);

        return [
            'lang' => $lang,
            'key' => $key,
            'value' => $this->getTranslationValue($key, $lang),
            'platform' => fake()->randomElement($platforms),
        ];
    }

    /**
     * Get translation value based on key and language
     */
    private function getTranslationValue(string $key, string $lang): string
    {
        $translations = [
            'en' => [
                'button.submit' => 'Submit',
                'button.cancel' => 'Cancel',
                'button.save' => 'Save',
                'button.delete' => 'Delete',
                'button.edit' => 'Edit',
                'label.email' => 'Email',
                'label.password' => 'Password',
                'label.username' => 'Username',
                'label.phone' => 'Phone Number',
                'message.success' => 'Operation completed successfully',
                'message.error' => 'An error occurred',
                'message.warning' => 'Warning: Please proceed with caution',
                'message.info' => 'For your information',
                'nav.home' => 'Home',
                'nav.about' => 'About',
                'nav.contact' => 'Contact',
                'nav.profile' => 'Profile',
                'nav.settings' => 'Settings',
                'form.required' => 'Required field',
                'form.optional' => 'Optional',
                'validation.required' => 'This field is required',
                'validation.email' => 'Please enter a valid email address',
                'validation.min' => 'Minimum length not met',
                'validation.max' => 'Maximum length exceeded',
                'page.title.home' => 'Welcome Home',
                'page.title.dashboard' => 'Dashboard',
                'page.title.profile' => 'User Profile',
                'error.404' => 'Page not found',
                'error.500' => 'Internal server error',
                'error.403' => 'Access forbidden',
            ],
            'es' => [
                'button.submit' => 'Enviar',
                'button.cancel' => 'Cancelar',
                'button.save' => 'Guardar',
                'button.delete' => 'Eliminar',
                'button.edit' => 'Editar',
                'label.email' => 'Correo electrónico',
                'label.password' => 'Contraseña',
                'label.username' => 'Nombre de usuario',
                'label.phone' => 'Número de teléfono',
                'message.success' => 'Operación completada exitosamente',
                'message.error' => 'Ocurrió un error',
                'message.warning' => 'Advertencia: Proceda con precaución',
                'message.info' => 'Para su información',
                'nav.home' => 'Inicio',
                'nav.about' => 'Acerca de',
                'nav.contact' => 'Contacto',
                'nav.profile' => 'Perfil',
                'nav.settings' => 'Configuración',
                'form.required' => 'Campo requerido',
                'form.optional' => 'Opcional',
                'validation.required' => 'Este campo es obligatorio',
                'validation.email' => 'Por favor ingrese un correo válido',
                'validation.min' => 'Longitud mínima no alcanzada',
                'validation.max' => 'Longitud máxima excedida',
                'page.title.home' => 'Bienvenido a Casa',
                'page.title.dashboard' => 'Panel de Control',
                'page.title.profile' => 'Perfil de Usuario',
                'error.404' => 'Página no encontrada',
                'error.500' => 'Error interno del servidor',
                'error.403' => 'Acceso prohibido',
            ],
            'fr' => [
                'button.submit' => 'Soumettre',
                'button.cancel' => 'Annuler',
                'button.save' => 'Enregistrer',
                'button.delete' => 'Supprimer',
                'button.edit' => 'Modifier',
                'label.email' => 'E-mail',
                'label.password' => 'Mot de passe',
                'label.username' => "Nom d'utilisateur",
                'label.phone' => 'Numéro de téléphone',
                'message.success' => 'Opération terminée avec succès',
                'message.error' => "Une erreur s'est produite",
                'message.warning' => 'Attention: Veuillez procéder avec prudence',
                'message.info' => 'Pour votre information',
                'nav.home' => 'Accueil',
                'nav.about' => 'À propos',
                'nav.contact' => 'Contact',
                'nav.profile' => 'Profil',
                'nav.settings' => 'Paramètres',
                'form.required' => 'Champ obligatoire',
                'form.optional' => 'Optionnel',
                'validation.required' => 'Ce champ est obligatoire',
                'validation.email' => 'Veuillez saisir une adresse e-mail valide',
                'validation.min' => 'Longueur minimale non atteinte',
                'validation.max' => 'Longueur maximale dépassée',
                'page.title.home' => 'Bienvenue à la Maison',
                'page.title.dashboard' => 'Tableau de bord',
                'page.title.profile' => 'Profil utilisateur',
                'error.404' => 'Page non trouvée',
                'error.500' => 'Erreur interne du serveur',
                'error.403' => 'Accès interdit',
            ],
        ];

        // Return translation if exists, otherwise generate a generic one
        return $translations[$lang][$key] ?? fake()->sentence(3);
    }

    /**
     * State for English translations
     */
    public function english(): static
    {
        return $this->state(fn (array $attributes) => [
            'lang' => 'en',
        ]);
    }

    /**
     * State for Spanish translations
     */
    public function spanish(): static
    {
        return $this->state(fn (array $attributes) => [
            'lang' => 'es',
        ]);
    }

    /**
     * State for French translations
     */
    public function french(): static
    {
        return $this->state(fn (array $attributes) => [
            'lang' => 'fr',
        ]);
    }

    /**
     * State for web platform
     */
    public function web(): static
    {
        return $this->state(fn (array $attributes) => [
            'platform' => 'web',
        ]);
    }

    /**
     * State for mobile platform
     */
    public function mobile(): static
    {
        return $this->state(fn (array $attributes) => [
            'platform' => 'mobile',
        ]);
    }

    /**
     * State for button translations
     */
    public function button(): static
    {
        $buttons = [
            'button.submit',
            'button.cancel',
            'button.save',
            'button.delete',
            'button.edit',
        ];

        return $this->state(function (array $attributes) use ($buttons) {
            $key = fake()->randomElement($buttons);
            return [
                'key' => $key,
                'value' => $this->getTranslationValue($key, $attributes['lang']),
            ];
        });
    }

    /**
     * State for validation messages
     */
    public function validation(): static
    {
        $validations = [
            'validation.required',
            'validation.email',
            'validation.min',
            'validation.max',
        ];

        return $this->state(function (array $attributes) use ($validations) {
            $key = fake()->randomElement($validations);
            return [
                'key' => $key,
                'value' => $this->getTranslationValue($key, $attributes['lang']),
            ];
        });
    }

    /**
     * State for navigation items
     */
    public function navigation(): static
    {
        $navItems = [
            'nav.home',
            'nav.about',
            'nav.contact',
            'nav.profile',
            'nav.settings',
        ];

        return $this->state(function (array $attributes) use ($navItems) {
            $key = fake()->randomElement($navItems);
            return [
                'key' => $key,
                'value' => $this->getTranslationValue($key, $attributes['lang']),
            ];
        });
    }
}
