<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()
                ->label('View')
                ->icon('heroicon-s-eye'),
            Actions\DeleteAction::make()
                ->label('Delete')
                ->icon('heroicon-s-trash'),
        ];
    }

    public function mutateFormDataBeforeSave(array $data): array
    {
        if (array_key_exists('password', $data) && filled($data['password'])) {
            $this->record->password = Hash::make($data['password']);
        }

        return $data;
    }

    protected function afterSave(): void
    {
        if ($this->record->id === Auth::id()) {
            session()->put('password_hash_' . $this->getGuardName(), $this->record->password);
        }
    }

    protected function getGuardName(): string
    {
        return Auth::getDefaultDriver();
    }
}
