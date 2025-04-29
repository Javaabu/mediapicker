<div class="button-group">
    <button type="submit" class="btn btn-success btn--icon-text btn--raised animate-submit">
        <i class="zmdi zmdi-check"></i> {{ __('Save') }}
    </button>
    <a class="btn btn-light btn--icon-text" href="{{ \Javaabu\Mediapicker\Mediapicker::newMediaInstance()->url('index') }}">
        <i class="zmdi zmdi-close"></i> {{ __('Cancel') }}
    </a>
</div>

<button class="btn btn-success btn--action animate-submit" type="submit"
        title="Save">
    <i class="zmdi zmdi-floppy"></i>
</button>
