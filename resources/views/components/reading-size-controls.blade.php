<div class="clinic-glass-controls clinic-size-controls" data-reading-controls>
    <button class="clinic-glass-toggle clinic-size-toggle" type="button" aria-label="Adjust text and element size" title="Adjust text and element size" aria-expanded="false" aria-controls="clinic-reading-settings" data-reading-toggle>
        <span aria-hidden="true">A<span>a</span></span>
    </button>
    <section class="clinic-glass-settings" id="clinic-reading-settings" aria-labelledby="clinic-reading-title" hidden>
        <div class="clinic-glass-heading"><label id="clinic-reading-title" for="clinic-reading-range">Reading size</label><output for="clinic-reading-range" data-reading-value>100%</output></div>
        <p id="clinic-reading-help">Enlarge text, tables, fields, and buttons on every page.</p>
        <div class="clinic-reading-adjustments">
            <button type="button" aria-label="Decrease text and element size" data-reading-decrease disabled><span aria-hidden="true">−</span></button>
            <input id="clinic-reading-range" class="clinic-glass-range" type="range" min="100" max="200" step="25" value="100" aria-describedby="clinic-reading-help" data-reading-range>
            <button type="button" aria-label="Increase text and element size" data-reading-increase><span aria-hidden="true">+</span></button>
        </div>
        <div class="clinic-glass-scale" aria-hidden="true"><span>Standard</span><span>Extra large</span></div>
        <div class="clinic-glass-footer"><span data-reading-storage>Saved on this browser</span><button type="button" data-reading-reset>Reset size</button></div>
    </section>
</div>
