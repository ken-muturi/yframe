<?php
	class welcome extends Core {
		private $model = '';
		
		public function __construct() 
		{
			parent::__construct();
			$this->model = new graffle;
		}

		public function index($version = null, $chapter = null )
		{
			if(! $chapter)
			{
				$chapter = array('book_id' => 1, 'chapter_id' => 1);
			}
			$book_chapter = new bible($chapter, 'kjv');

			$view_data = new View('index');
			$view_data->book_chapter = $book_chapter;
			$view_data->render();
		}

		public function test()
		{
			util::printr(date('Y-m-d', strtotime('next Sunday')));
			util::printr(date('Y-m-d', strtotime('next Tuesday')));
			util::printr(date('Y-m-d', strtotime('next Wednesday')));
		}

		public function dao($version = null, $verse = 1 )
		{
			$version = new bible($verse, $version);
			$books = new book();

			foreach ($books as $book) 
			{
				util::printr($book->name);
			}

			//util::printr($version->book->testament->name);
			//util::printr($version->book->testament->objs_query);
		}

		public function exam()
		{
			$chars = range('A', 'Z');
			$counter = 115 % 30 + 1;
			$x = '';
			for ($i=0; $i < $counter ; $i++) 
			{ 
				if($counter == $i || $i == 11) 
				$x .= $chars[$i * 2] . $chars[$i % 3 + 6] . $chars[$i / 2 + 7.5] .chr(33);
				
				if($counter != $i && $i == 3) 
				$x .= $chars[$i * 8] . $chars[$i + 11] . $chars[$i * 7 - 1] .chr(32);
			}
			util::printr($x);
		}		

		public function ran()
		{
			util::printr(Encrypt::instance()->crypt('kevin.amulega') );
			util::printr(Encrypt::instance()->decrypt(Encrypt::instance()->crypt('kevin.amulega')) );
		}

		public function save()
		{
			$vars = $this->model->create($_POST);

			$view_data = new View('index');
			$view_data->shapes = $vars['shapes'];
			$view_data->script = $vars['script'];
			$view_data->render();
		}
	}
