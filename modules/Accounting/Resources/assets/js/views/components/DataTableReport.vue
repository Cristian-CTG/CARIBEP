<template>
    <div>
        <div class="table-resposive">
            <table class="table">
                <thead>
                    <tr>
                        <th v-for="column in columns" :key="column.field">{{ column.label }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="row in data" :key="row.code">
                        <td v-for="column in columns" :key="column.field">{{ row[column.field] }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>

<script>
export default {
    props: {
        data: { type: Object, required: true },
        columns: { type: Array, required: true },
    },
};
</script>