<script type="riot/tag">
	<bootstrap-modal>	
		<div class='modal fade'>
		    <div class='modal-dialog'>
		        <div class='modal-content'>
		            <div class='modal-header'>
		                <button type='button' class='close' data-dismiss='modal'>
		                    <span aria-hidden='true'>&times;</span><span class='sr-only'>{ opts['closeText'] }</span>
		                </button>
		                <h4 class='modal-title'>
		                    { opts['titleText'] }
		                    <div class='pull-right'></div>
		                </h4>
		            </div>
		            <div class='modal-body'>            
		                <yield/>
		            </div>
		            <div class='modal-footer' if={ this.buttons.length }>

		            	<bootstrap-button each={ btn, index in this.buttons } type="{ btn.type }" text="{ btn.text }" icon="{ btn.icon }" direction="{ btn.direction }"></bootstrap-button>		            
		            </div>
		        </div>
		    </div>
		</div>

		this.buttons = JSON.parse(this.root.getAttribute('buttons'));

	</bootstrap-modal>
</script>

<script type="riot/tag">
	<bootstrap-button>
		<button type='button' class='btn btn-{ opts.type } pull-{ opts.direction }'><i class="{ opts.icon }"></i>{ opts.text}</button>
	</bootstrap-button>
</script>