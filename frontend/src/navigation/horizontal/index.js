export default [
	{
		title: 'Dashboard',
		to: { name: 'root' },
		icon: { icon: 'tabler-smart-home' },
	},
	{
		title: 'Product Management',
		icon: { icon: 'tabler-package' },
		children: [
			{
				title: 'Product List',
				to: { name: 'products' },
				icon: { icon: 'tabler-list' },
			},
		],
	},
]
