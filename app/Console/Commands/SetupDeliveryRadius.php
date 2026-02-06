<?php

namespace App\Console\Commands;

use App\Modules\Commerce\Models\DeliveryRadius;
use Illuminate\Console\Command;

class SetupDeliveryRadius extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'delivery:setup-radius
                            {--radius=300 : Default delivery radius in km}
                            {--reset : Reset to default configuration}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Setup and configure delivery radius for driver matching';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if ($this->option('reset')) {
            return $this->resetConfiguration();
        }

        $radiusKm = (float) $this->option('radius');

        if ($radiusKm <= 0) {
            $this->error('Radius must be greater than 0');
            return 1;
        }

        try {
            $existing = DeliveryRadius::where('name', 'default')->first();

            if ($existing) {
                $existing->update(['radius_km' => $radiusKm]);
                $this->info("✓ Updated default delivery radius to {$radiusKm} km");
            } else {
                DeliveryRadius::create([
                    'id' => \Illuminate\Support\Str::uuid(),
                    'name' => 'default',
                    'radius_km' => $radiusKm,
                    'description' => 'Default delivery radius for driver matching',
                    'is_active' => true,
                ]);
                $this->info("✓ Created default delivery radius: {$radiusKm} km");
            }

            $this->displayConfiguration();
            return 0;
        } catch (\Exception $e) {
            $this->error("Error: {$e->getMessage()}");
            return 1;
        }
    }

    /**
     * Reset to default configuration
     */
    private function resetConfiguration(): int
    {
        try {
            DeliveryRadius::where('name', 'default')->delete();
            
            DeliveryRadius::create([
                'id' => \Illuminate\Support\Str::uuid(),
                'name' => 'default',
                'radius_km' => 15,
                'description' => 'Default delivery radius for driver matching',
                'is_active' => true,
            ]);

                $this->info('✓ Reset delivery configuration to defaults');
            $this->displayConfiguration();
            return 0;
        } catch (\Exception $e) {
            $this->error("Error: {$e->getMessage()}");
            return 1;
        }
    }

    /**
     * Display current configuration
     */
    private function displayConfiguration(): void
    {
        $config = DeliveryRadius::where('name', 'default')->first();

        if (!$config) {
            $this->warn('No delivery radius configuration found');
            return;
        }

        $this->table(
            ['Setting', 'Value'],
            [
                ['Delivery Radius', "{$config->radius_km} km"],
                ['Status', $config->is_active ? 'Active' : 'Inactive'],
                ['Description', $config->description ?? 'N/A'],
                ['Updated At', $config->updated_at->toDateTimeString()],
            ]
        );
    }
}
