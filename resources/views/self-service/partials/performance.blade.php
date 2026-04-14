<section class="table-card {{ $activeTab === 'performance' ? '' : 'hidden-tab' }}">
    <div class="section-head">
        <h2>Performance</h2>
        <p>Finalized monthly and bi-annual reviews only.</p>
    </div>

    <div class="performance-grid">
        @forelse($performanceEvaluations as $evaluation)
            <article class="performance-card">
                <div class="performance-card-head">
                    <div>
                        <h3>{{ $evaluation->periodLabel() }}</h3>
                        <p>{{ \App\Models\PerformanceEvaluation::types()[$evaluation->evaluation_type] ?? ucfirst($evaluation->evaluation_type) }}</p>
                    </div>
                    <span class="status-pill finalized">Finalized</span>
                </div>
                <div class="metric-grid">
                    @foreach([
                        'Performance' => $evaluation->hr_performance,
                        'Punctuality' => $evaluation->hr_punctuality,
                        'Behaviour' => $evaluation->hr_behaviour,
                        'Learning' => $evaluation->hr_learning,
                        'Participation' => $evaluation->hr_participation,
                    ] as $label => $value)
                        <div class="metric-chip">
                            <span>{{ $label }}</span>
                            <strong>{{ $value ?: 'N/A' }}/5</strong>
                        </div>
                    @endforeach
                </div>
                <div class="self-service-two-col feedback-grid">
                    <div class="feedback-card">
                        <span>Manager Feedback</span>
                        <p>{{ $evaluation->manager_feedback ?: 'No manager feedback recorded.' }}</p>
                    </div>
                    <div class="feedback-card">
                        <span>HR Feedback</span>
                        <p>{{ $evaluation->hr_feedback ?: 'No HR feedback recorded.' }}</p>
                    </div>
                </div>
                <div class="performance-footer">
                    <span>Manager Avg: {{ number_format((float) ($evaluation->managerAverage() ?? 0), 2) }}</span>
                    <span>Final Avg: {{ number_format((float) ($evaluation->hrAverage() ?? 0), 2) }}</span>
                </div>
            </article>
        @empty
            <div class="self-service-empty">
                <div class="empty-icon"><i data-lucide="chart-column-big"></i></div>
                <h2>No Finalized Reviews Yet</h2>
                <p>Finalized performance evaluations will appear here after manager contribution and HR finalization are completed.</p>
            </div>
        @endforelse
    </div>
</section>
