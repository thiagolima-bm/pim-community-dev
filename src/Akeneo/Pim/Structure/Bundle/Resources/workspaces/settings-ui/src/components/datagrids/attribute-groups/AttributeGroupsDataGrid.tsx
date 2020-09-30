import React, {FC} from 'react';
import {useTranslate} from '@akeneo-pim-community/legacy-bridge';
import {useAttributeGroupPermissions, useAttributeGroupsDataGridState, useGetAttributeGroupLabel} from '../../../hooks';
import {AttributeGroup} from '../../../models';
import {DataGrid} from '../../shared';
import {ColumnLabel} from "./ColumnLabel";

type Props = {
  groups: AttributeGroup[];
};

const AttributeGroupsDataGrid: FC<Props> = ({groups}) => {
  const {refreshOrder, compare, saveOrder, redirect} = useAttributeGroupsDataGridState();
  const {sortGranted, editGranted} = useAttributeGroupPermissions();
  const getLabel = useGetAttributeGroupLabel();
  const translate = useTranslate();

  return (
    <DataGrid
      isDraggable={sortGranted}
      dataSource={groups}
      handleAfterMove={refreshOrder}
      compareData={compare}
    >
      <DataGrid.HeaderRow>
        <DataGrid.Column>{translate('pim_enrich.entity.attribute_group.grid.columns.name')}</DataGrid.Column>
      </DataGrid.HeaderRow>
      <DataGrid.Body
        onRowClick={(group: AttributeGroup) => {
          if (editGranted) {
            redirect(group);
          }
        }}
        onRowMoveEnd={() => {
          (async () => saveOrder())();
        }}
      >
        {groups.map((group) => (
          <DataGrid.Row
            key={group.code}
            data={group}
          >
            <DataGrid.Column>
              <ColumnLabel>{getLabel(group)}</ColumnLabel>
            </DataGrid.Column>
          </DataGrid.Row>
        ))}
      </DataGrid.Body>
    </DataGrid>
  );
};

export {AttributeGroupsDataGrid};