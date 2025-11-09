<?php

namespace App\Filament\Resources\NvqHistoriesResource\RelationManagers;

use App\Models\NvqReviews;
use App\Models\ReviewType;
use Filament\Forms;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Actions\Action;

class NvqReviewRelationManager extends RelationManager {
    protected static string $relationship = 'nvqReview';

    public function form(Form $form): Form {
        return $form
            ->schema([
                DatePicker::make('completed_date')
                    ->label('Date')
                    ->native(false)
                    ->required()
                    ->displayFormat('d/m/Y')
                    ->default(now())
                    ->closeOnDateSelection()
                    ->prefixIcon('heroicon-o-calendar')
                    ->placeholder('Select Date'),
                Select::make('status')
                    ->native(false)
                    ->options([
                        'Profiled' => 'Profiled',
                        'Induction' => 'Induction',
                        'Assessment' => 'Assessment',
                        'Completed' => 'Completed',
                        'None' => 'None',
                    ]),
                Select::make('completed')
                    ->label('Contact Mode')
                    ->native(false)
                    ->options([
                        '1' => 'Yes',
                        '0' => 'No',
                    ]),
                Select::make('type')
                    ->native(false)
                    ->options(ReviewType::pluck('name', 'id')->toArray())
                    ->searchable()
                    ->label('Review Type')
                    ->placeholder('Select Review Type')
                    ->required(),
                Textarea::make('notes')
                    ->label('Notes')
                    ->placeholder('Enter notes here')
                    ->rows(3)
                    ->columnSpanFull()
                    ->required(),
                Checkbox::make('is_manually')
                    ->label('Manually add next contact date')
                    ->hiddenOn(['edit'])
                    ->reactive()
                    ->dehydrated(false)
                    ->afterStateUpdated(fn($state, callable $set) => $set('due_date', null)),
                DatePicker::make('due_date')
                    ->label('Next Contact Due')
                    ->native(false)
                    ->displayFormat('d/m/Y')
                    ->default(now())
                    ->prefixIcon('heroicon-o-calendar')
                    ->columnSpanFull()
                    ->placeholder('Select Date')
                    ->hidden(fn(callable $get) => !$get('is_manually')),

            ]);
    }

    public function table(Table $table): Table {
        return $table
            ->recordTitleAttribute('NvqReviewRelationManager')
            ->columns([
                Tables\Columns\TextColumn::make('completed_date')->date('d-m-Y')
                    ->label('Date')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('completed')
                    ->label('Review Completed')
                    ->badge()
                    ->color(fn($record) => ($record->completed == 1) ? 'success' : 'danger')
                    ->getStateUsing(fn($record) => ($record->completed == 1) ? 'Yes' : 'No'),
                Tables\Columns\TextColumn::make('reviewType.name')
                    ->label('Review Type')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->sortable()
                    ->searchable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Add NVQ Review')
                    ->modalHeading('Create NVQ Review')
                    ->extraModalWindowAttributes([
                        'class' => 'evv create-nvq-review-btn'
                    ])
                    ->after(function (array $data) {
                        return $this->handleRecordCreation($data);
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->iconButton()
                    ->modalHeading('Edit NVQ Review')
                    ->extraModalWindowAttributes([
                        'class' => 'evv save-nvq-review-btn'
                    ]),
                Tables\Actions\DeleteAction::make()->iconButton()->modalHeading('Delete NVQ Review'),
            ])
            ->actionsColumnLabel('Action')
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('No NVQ reviews')
            ->emptyStateDescription('Create an NVQ review to get started.');
    }

    public static function getTitle(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): string {
        return 'NVQ Review Diary';
    }
    public function isReadOnly(): bool {
        return false;
    }

    protected function handleRecordCreation(array $data) {
        $nvq = $this->getOwnerRecord();
        if ($nvq && !empty($data['status'])) {
            if ($data['status'] == 'Induction') {
                $nvq->status = "Working Towards";
                $nvq->save();
            }
            if ($data['status'] == 'Assessment') {
                $nvq->status = "Assessed (awaiting evidence)";
                $nvq->save();
            }
            if ($data['status'] == 'Completed') {
                $nvq->status = "Needs IV";
                $nvq->save();
            }
        }
        return $data;
    }
}
