<div class="clinic-glass-controls" data-glass-controls>
    <button class="clinic-glass-toggle" type="button" aria-label="Adjust background blur" title="Adjust background blur" aria-expanded="false" aria-controls="clinic-glass-settings" data-glass-toggle>
        <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="5"/><path d="M8 7v10M12 9v6M16 10v4"/></svg>
    </button>
    <section class="clinic-glass-settings" id="clinic-glass-settings" aria-labelledby="clinic-glass-title" hidden>
        <div class="clinic-glass-heading">
            <label id="clinic-glass-title" for="clinic-glass-range">Background blur</label>
            <output for="clinic-glass-range" data-glass-value>22 px</output>
        </div>
        <p id="clinic-glass-help">Soften the background behind text on every page.</p>
        <input id="clinic-glass-range" class="clinic-glass-range" type="range" min="0" max="40" step="1" value="22" aria-describedby="clinic-glass-help" data-glass-range>
        <div class="clinic-glass-scale" aria-hidden="true"><span>Clear</span><span>Frosted</span></div>
        <div class="clinic-glass-footer"><span>Saved on this browser</span><button type="button" data-glass-reset>Reset blur</button></div>
    </section>
</div>
